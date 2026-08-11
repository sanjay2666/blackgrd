<?php

namespace App\Services;

use App\Models\PrintingDesign;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class PrintingDesignMasterService
{
    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'sale_order_items', 'work_orders', 'work_order_items', 'work_process_requirements',
        'work_purchase_requirements', 'work_inspection_details', 'work_inspections',
        'warehouse_in_items', 'warehouse_out_items', 'warehouse_balance_items',
        'warehouse_item_stocks', 'purchase_items', 'purchase_order_items',
        'stock_mill_dispatch_items', 'receive_stock_mill_dispatch_items',
        'stock_mill_return_items', 'department_returns', 'gate_passes',
    ];

    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly DatabaseManager $database,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array<string, mixed> $attributes */
    public function save(PrintingDesign $design, array $attributes): PrintingDesign
    {
        $creating = ! $design->exists;
        $before = $creating ? null : $this->snapshot($design);
        $name = trim((string) $attributes['design_name']);
        $code = strtoupper(trim((string) ($attributes['design_code'] ?? '')));
        $companyId = $this->organization->companyId();

        $duplicate = PrintingDesign::query()->where('company_id', $companyId)->where('status', '!=', 'Deleted')
            ->where(function ($query) use ($name, $code): void {
                $query->whereRaw('LOWER(TRIM(design_name)) = ?', [strtolower($name)]);
                if ($code !== '') {
                    $query->orWhereRaw('UPPER(TRIM(design_code)) = ?', [$code]);
                }
            })
            ->when($design->exists, fn ($query) => $query->whereKey('!=', $design->getKey()))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['design_name' => 'This Printing Design name or code already exists.']);
        }

        $this->protectIdentity($design, $attributes);
        $design->fill([
            'company_id' => $companyId,
            'design_name' => $name,
            'design_code' => $code !== '' ? $code : null,
            'description' => filled($attributes['description'] ?? null) ? trim((string) $attributes['description']) : null,
            'display_order' => $attributes['display_order'] ?? null,
            'status' => $attributes['status'],
            'updated_at' => now(),
        ]);
        if ($creating) {
            $design->created_at = now();
        }
        $design->save();
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'printing_design_created' : 'printing_design_updated',
            'description' => 'Printing Design master saved.', 'auditable_type' => $design->getMorphClass(),
            'auditable_id' => $design->getKey(), 'old_values' => $before,
            'new_values' => $this->snapshot($design->fresh()),
        ]);

        return $design->fresh();
    }

    public function transition(PrintingDesign $design, string $status): void
    {
        $before = ['status' => $design->getRawOriginal('status')];
        $design->update(['status' => $status, 'updated_at' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => strtolower($status), 'event' => 'printing_design_'.strtolower($status),
            'description' => 'Printing Design status changed.', 'auditable_type' => $design->getMorphClass(),
            'auditable_id' => $design->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status],
        ]);
    }

    public function rejectDeletion(PrintingDesign $design): never
    {
        if ($this->isReferenced($design)) {
            throw ValidationException::withMessages(['design' => 'Referenced Printing Designs cannot be deleted; deactivate them instead.']);
        }
        throw ValidationException::withMessages(['design' => 'Printing Design identities are retained for history; deactivate them instead.']);
    }

    public function isReferenced(PrintingDesign $design): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            foreach (['printing_design_id', 'print_design_id', 'design_id'] as $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                    && $this->database->table($table)->where($column, $design->getKey())->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function snapshot(PrintingDesign $design): array
    {
        return $design->only(['id', 'company_id', 'design_name', 'design_code', 'description', 'display_order', 'status']);
    }

    /** @param array<string, mixed> $attributes */
    private function protectIdentity(PrintingDesign $design, array $attributes): void
    {
        if (! $design->exists || ! $this->isReferenced($design)) {
            return;
        }
        foreach (['design_name', 'design_code'] as $field) {
            if (array_key_exists($field, $attributes) && trim((string) ($design->getRawOriginal($field) ?? '')) !== trim((string) ($attributes[$field] ?? ''))) {
                throw ValidationException::withMessages([$field => 'Referenced Printing Design identity fields cannot be changed.']);
            }
        }
    }
}
