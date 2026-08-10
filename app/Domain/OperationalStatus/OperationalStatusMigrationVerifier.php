<?php

namespace App\Domain\OperationalStatus;

use App\Enums\GatePassStatus;
use App\Enums\InspectionResult;
use App\Enums\InspectionStatus;
use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryMovementStatus;
use App\Enums\InventoryReceiptStatus;
use App\Enums\JobWorkStatus;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Enums\SaleOrderDocumentStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Enums\WorkRequirementStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class OperationalStatusMigrationVerifier
{
    /** @var array<string, list<string>> */
    private const PRESERVED_TABLES = [
        'sale_orders' => [],
        'sale_order_items' => ['quantity'],
        'purchase_orders' => [],
        'purchase_order_items' => ['quantity', 'received_quantity'],
        'work_orders' => [],
        'work_process_requirements' => ['quantity', 'alloted_quantity'],
        'work_inspections' => [],
        'work_inspection_details' => [],
        'warehouse_in_items' => ['item_qty'],
        'warehouse_out_items' => ['item_qty'],
        'warehouse_balance_items' => ['item_qty'],
        'warehouse_item_stocks' => ['item_qty', 'insp_bal_quan_size'],
        'gate_passes' => [],
        'stock_mill_dispatches' => ['tot_dispatch_mtr', 'tot_receive_mtr'],
        'stock_mill_dispatch_items' => ['dispatch_meter'],
        'receive_stock_mill_dispatches' => ['tot_receive_mtr'],
        'receive_stock_mill_dispatch_items' => ['receive_meter'],
    ];

    /** @var array<string, array<string, class-string<\BackedEnum>>> */
    private const STATUS_COLUMNS = [
        'sale_orders' => ['document_status' => SaleOrderDocumentStatus::class],
        'purchase_orders' => ['document_status' => PurchaseOrderDocumentStatus::class],
        'purchase_order_items' => ['receipt_status' => InventoryReceiptStatus::class],
        'work_orders' => [
            'execution_status' => WorkOrderExecutionStatus::class,
            'inspection_status' => InspectionStatus::class,
        ],
        'work_process_requirements' => [
            'requirement_status' => WorkRequirementStatus::class,
            'allocation_status' => InventoryAllocationStatus::class,
        ],
        'work_inspections' => [
            'inspection_status' => InspectionStatus::class,
            'inspection_result' => InspectionResult::class,
        ],
        'work_inspection_details' => ['inspection_result' => InspectionResult::class],
        'warehouse_in_items' => [
            'movement_status' => InventoryMovementStatus::class,
            'receipt_status' => InventoryReceiptStatus::class,
        ],
        'warehouse_out_items' => ['movement_status' => InventoryMovementStatus::class],
        'warehouse_balance_items' => ['movement_status' => InventoryMovementStatus::class],
        'warehouse_item_stocks' => ['allocation_status' => InventoryAllocationStatus::class],
        'gate_passes' => ['gate_pass_status' => GatePassStatus::class],
        'stock_mill_dispatches' => ['job_work_status' => JobWorkStatus::class],
        'stock_mill_dispatch_items' => ['receipt_status' => InventoryReceiptStatus::class],
        'receive_stock_mill_dispatches' => ['receipt_status' => InventoryReceiptStatus::class],
        'receive_stock_mill_dispatch_items' => ['receipt_status' => InventoryReceiptStatus::class],
    ];

    public function __construct(private readonly LegacyOperationalStatusMapper $mapper) {}

    /** @return array<string, mixed> */
    public function preservationSnapshot(): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS database_name')->database_name;
        $snapshot = [];

        foreach (self::PRESERVED_TABLES as $table => $quantityColumns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required Task 1.3C table is missing: {$table}");
            }

            $ids = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id): string => (string) $id)->all();
            $quantities = [];

            foreach ($quantityColumns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $quantities[$column] = (string) DB::table($table)->selectRaw("COALESCE(SUM(`{$column}`), 0) AS total")->value('total');
                }
            }

            $snapshot[$table] = [
                'row_count' => count($ids),
                'id_sha256' => hash('sha256', implode(',', $ids)),
                'minimum_id' => $ids === [] ? null : $ids[0],
                'maximum_id' => $ids === [] ? null : $ids[array_key_last($ids)],
                'quantities' => $quantities,
                'auto_increment' => DB::table('information_schema.TABLES')
                    ->where('TABLE_SCHEMA', $database)
                    ->where('TABLE_NAME', $table)
                    ->value('AUTO_INCREMENT'),
            ];
        }

        return $snapshot;
    }

    /** @param array<string, mixed> $before @param array<string, mixed> $after */
    public function assertPreserved(array $before, array $after): void
    {
        if ($before !== $after) {
            $changed = collect(array_keys($before))
                ->filter(fn (string $table): bool => ($before[$table] ?? null) !== ($after[$table] ?? null))
                ->implode(', ');

            throw new RuntimeException('Preserved row/ID/quantity/auto-increment snapshot changed: '.$changed);
        }
    }

    /** @return array{backfilled: array<string, int>, distributions: array<string, array<string, int>>, exclusions: list<array<string, mixed>>} */
    public function verifyCanonicalBackfill(): array
    {
        $this->assertColumnsAndAllowedValues();
        $exclusions = [];

        $this->verifySaleOrders($exclusions);
        $this->verifyPurchaseOrders($exclusions);
        $this->verifyWorkOrders();
        $this->verifyRequirements();
        $this->verifyInspections($exclusions);
        $this->verifyInventory($exclusions);
        $this->verifyGatePasses($exclusions);
        $this->verifyJobWork($exclusions);

        $backfilled = [];
        $distributions = [];

        foreach (self::STATUS_COLUMNS as $table => $columns) {
            foreach (array_keys($columns) as $column) {
                $key = $table.'.'.$column;
                $backfilled[$key] = DB::table($table)->whereNotNull($column)->count();
                $distributions[$key] = DB::table($table)
                    ->select($column, DB::raw('COUNT(*) AS aggregate'))
                    ->groupBy($column)
                    ->orderBy($column)
                    ->pluck('aggregate', $column)
                    ->mapWithKeys(fn ($count, $status): array => [($status ?? '(null)') => (int) $count])
                    ->all();
            }
        }

        return compact('backfilled', 'distributions', 'exclusions');
    }

    /** @return list<array<string, mixed>> */
    public function legacyExclusions(): array
    {
        $exclusions = [];

        foreach (['sale_orders', 'purchase_orders', 'purchase_order_items', 'warehouse_in_items', 'warehouse_out_items', 'warehouse_balance_items', 'warehouse_item_stocks', 'gate_passes', 'stock_mill_dispatches', 'stock_mill_dispatch_items', 'receive_stock_mill_dispatches', 'receive_stock_mill_dispatch_items'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
                continue;
            }

            foreach (DB::table($table)->where('status', 'Deleted')->orderBy('id')->pluck('id') as $id) {
                $exclusions[] = ['table' => $table, 'id' => $id, 'reason' => 'deleted record; business status is not inferred'];
            }
        }

        foreach ([
            ['work_inspections', 'insp_work_status'],
            ['work_inspection_details', 'work_status'],
        ] as [$table, $column]) {
            foreach (DB::table($table)->orderBy('id')->get(['id', $column]) as $row) {
                if ($this->mapper->inspectionResult($row->{$column}) === null) {
                    $exclusions[] = [
                        'table' => $table,
                        'id' => $row->id,
                        'legacy_value' => $row->{$column},
                        'reason' => 'unrecognized inspection result will remain null',
                    ];
                }
            }
        }

        return $exclusions;
    }

    private function assertColumnsAndAllowedValues(): void
    {
        foreach (self::STATUS_COLUMNS as $table => $columns) {
            foreach ($columns as $column => $enum) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("Canonical column is missing: {$table}.{$column}");
                }

                $allowed = array_map(fn (\BackedEnum $case): string => (string) $case->value, $enum::cases());
                $invalid = DB::table($table)->whereNotNull($column)->whereNotIn($column, $allowed)->count();

                if ($invalid !== 0) {
                    throw new RuntimeException("Invalid canonical value found in {$table}.{$column}: {$invalid} row(s)");
                }
            }
        }
    }

    /** @param list<array<string, mixed>> $exclusions */
    private function verifySaleOrders(array &$exclusions): void
    {
        foreach (DB::table('sale_orders')->orderBy('id')->get() as $order) {
            if ($order->status === 'Deleted') {
                $this->assertValue('sale_orders', $order->id, 'document_status', $order->document_status, null);
                $exclusions[] = ['table' => 'sale_orders', 'id' => $order->id, 'reason' => 'deleted record; business status not inferred'];

                continue;
            }

            $items = DB::table('sale_order_items')->where('sale_order_id', $order->id)
                ->where('status', '!=', 'Deleted')->where('is_deleted', false)
                ->get(['id', 'is_work_completed', 'is_work_final_completed']);
            $itemIds = $items->pluck('id');
            $hasWorkOrder = $itemIds->isNotEmpty() && DB::table('work_order_items')
                ->whereIn('sale_order_item_id', $itemIds)->where('status', '!=', 'Deleted')->exists();
            $complete = $items->isNotEmpty() && $items->every(
                fn (object $item): bool => (int) $item->is_work_completed === 1 || (int) $item->is_work_final_completed === 1
            );
            $this->assertValue('sale_orders', $order->id, 'document_status', $order->document_status, $this->mapper->saleOrder($hasWorkOrder, $complete)->value);
        }
    }

    /** @param list<array<string, mixed>> $exclusions */
    private function verifyPurchaseOrders(array &$exclusions): void
    {
        foreach (DB::table('purchase_order_items')->orderBy('id')->get() as $item) {
            $deleted = $item->status === 'Deleted' || (bool) $item->is_deleted;
            $expected = $deleted ? null : $this->mapper->purchaseReceipt((float) $item->quantity, (float) $item->received_quantity)->value;
            $this->assertValue('purchase_order_items', $item->id, 'receipt_status', $item->receipt_status, $expected);
            if ($deleted) {
                $exclusions[] = ['table' => 'purchase_order_items', 'id' => $item->id, 'reason' => 'deleted record; receipt status not inferred'];
            }
        }

        foreach (DB::table('purchase_orders')->orderBy('id')->get() as $order) {
            $deleted = $order->status === 'Deleted' || $order->is_deleted === 'Yes';
            if ($deleted) {
                $this->assertValue('purchase_orders', $order->id, 'document_status', $order->document_status, null);
                $exclusions[] = ['table' => 'purchase_orders', 'id' => $order->id, 'reason' => 'deleted record; business status not inferred'];

                continue;
            }
            $statuses = DB::table('purchase_order_items')->where('purchase_id', $order->id)
                ->where('status', '!=', 'Deleted')->where('is_deleted', false)->whereNotNull('receipt_status')
                ->pluck('receipt_status')->map(fn (string $status): InventoryReceiptStatus => InventoryReceiptStatus::from($status))->all();
            $this->assertValue('purchase_orders', $order->id, 'document_status', $order->document_status, $this->mapper->purchaseOrder($statuses)->value);
        }
    }

    private function verifyWorkOrders(): void
    {
        foreach (DB::table('work_orders')->orderBy('id')->get() as $row) {
            $hasRequirement = DB::table('work_process_requirements')->where('work_order_id', $row->id)->where('status', '!=', 'Deleted')->exists();
            $this->assertValue('work_orders', $row->id, 'execution_status', $row->execution_status, $this->mapper->workOrder($row, $hasRequirement)->value);
            $this->assertValue('work_orders', $row->id, 'inspection_status', $row->inspection_status, $row->insp_status === 'Complete' ? 'completed' : 'pending');
        }
    }

    private function verifyRequirements(): void
    {
        foreach (DB::table('work_process_requirements')->orderBy('id')->get() as $row) {
            $decision = (int) $row->is_accept;
            $required = (float) $row->quantity;
            $allotted = (float) $row->alloted_quantity;
            $this->assertValue('work_process_requirements', $row->id, 'requirement_status', $row->requirement_status, $this->mapper->workRequirement($decision, $required, $allotted)->value);
            $this->assertValue('work_process_requirements', $row->id, 'allocation_status', $row->allocation_status, $this->mapper->allocation($required, $allotted, $decision)->value);
        }
    }

    /** @param list<array<string, mixed>> $exclusions */
    private function verifyInspections(array &$exclusions): void
    {
        foreach (DB::table('work_inspections')->orderBy('id')->get() as $row) {
            $result = $this->mapper->inspectionResult($row->insp_work_status)?->value;
            $this->assertValue('work_inspections', $row->id, 'inspection_status', $row->inspection_status, $row->insp_status === 'Complete' ? 'completed' : 'pending');
            $this->assertValue('work_inspections', $row->id, 'inspection_result', $row->inspection_result, $result);
            if ($result === null) {
                $exclusions[] = ['table' => 'work_inspections', 'id' => $row->id, 'legacy_value' => $row->insp_work_status, 'reason' => 'unrecognized result retained as null'];
            }
        }
        foreach (DB::table('work_inspection_details')->orderBy('id')->get() as $row) {
            $result = $this->mapper->inspectionResult($row->work_status)?->value;
            $this->assertValue('work_inspection_details', $row->id, 'inspection_result', $row->inspection_result, $result);
            if ($result === null) {
                $exclusions[] = ['table' => 'work_inspection_details', 'id' => $row->id, 'legacy_value' => $row->work_status, 'reason' => 'unrecognized result retained as null'];
            }
        }
    }

    /** @param list<array<string, mixed>> $exclusions */
    private function verifyInventory(array &$exclusions): void
    {
        foreach (['warehouse_in_items', 'warehouse_out_items', 'warehouse_balance_items'] as $table) {
            foreach (DB::table($table)->orderBy('id')->get() as $row) {
                $deleted = $row->status === 'Deleted';
                $this->assertValue($table, $row->id, 'movement_status', $row->movement_status, $deleted ? null : 'posted');
                if ($table === 'warehouse_in_items') {
                    $this->assertValue($table, $row->id, 'receipt_status', $row->receipt_status, $deleted ? null : 'received');
                }
                if ($deleted) {
                    $exclusions[] = ['table' => $table, 'id' => $row->id, 'reason' => 'deleted record; movement status not inferred'];
                }
            }
        }
        foreach (DB::table('warehouse_item_stocks')->orderBy('id')->get() as $row) {
            $deleted = $row->status === 'Deleted';
            $expected = $deleted ? null : ($row->is_allotted_stock === 'Yes' ? 'allocated' : 'unallocated');
            $this->assertValue('warehouse_item_stocks', $row->id, 'allocation_status', $row->allocation_status, $expected);
            if ($deleted) {
                $exclusions[] = ['table' => 'warehouse_item_stocks', 'id' => $row->id, 'reason' => 'deleted record; allocation status not inferred'];
            }
        }
    }

    /** @param list<array<string, mixed>> $exclusions */
    private function verifyGatePasses(array &$exclusions): void
    {
        foreach (DB::table('gate_passes')->orderBy('id')->get() as $row) {
            $expected = $this->mapper->gatePass($row->status, $row->is_item_received_in_warehouse === 'Yes', $row->gatepass_number)?->value;
            $this->assertValue('gate_passes', $row->id, 'gate_pass_status', $row->gate_pass_status, $expected);
            if ($expected === null) {
                $exclusions[] = ['table' => 'gate_passes', 'id' => $row->id, 'reason' => 'deleted record; cancellation was not guessed'];
            }
        }
    }

    /** @param list<array<string, mixed>> $exclusions */
    private function verifyJobWork(array &$exclusions): void
    {
        foreach (DB::table('stock_mill_dispatches')->orderBy('id')->get() as $row) {
            $deleted = $row->status === 'Deleted';
            $expected = $deleted ? null : match (true) {
                (bool) $row->is_tot_mtr_received => JobWorkStatus::Received->value,
                (float) $row->tot_receive_mtr > 0 => JobWorkStatus::PartiallyReceived->value,
                default => JobWorkStatus::Dispatched->value,
            };
            $this->assertValue('stock_mill_dispatches', $row->id, 'job_work_status', $row->job_work_status, $expected);
            if ($deleted) {
                $exclusions[] = ['table' => 'stock_mill_dispatches', 'id' => $row->id, 'reason' => 'deleted record; job status not inferred'];
            }
        }

        foreach ([
            'stock_mill_dispatch_items' => 'pending',
            'receive_stock_mill_dispatches' => 'received',
            'receive_stock_mill_dispatch_items' => 'received',
        ] as $table => $activeStatus) {
            foreach (DB::table($table)->orderBy('id')->get() as $row) {
                $deleted = $row->status === 'Deleted';
                $this->assertValue($table, $row->id, 'receipt_status', $row->receipt_status, $deleted ? null : $activeStatus);
                if ($deleted) {
                    $exclusions[] = ['table' => $table, 'id' => $row->id, 'reason' => 'deleted record; receipt status not inferred'];
                }
            }
        }
    }

    private function assertValue(string $table, mixed $id, string $column, mixed $actual, mixed $expected): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("Backfill mismatch at {$table}#{$id}.{$column}: expected [".($expected ?? 'NULL').'] found ['.($actual ?? 'NULL').']');
        }
    }
}
