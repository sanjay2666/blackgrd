<?php

namespace App\Services;

use App\Models\FabricFaultReason;
use App\Models\ProcessItem;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class FabricFaultReasonMasterService
{
    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'work_inspections', 'work_inspection_details', 'gate_passes', 'warehouse_in_items',
        'warehouse_out_items', 'warehouse_balance_items', 'warehouse_item_stocks',
        'work_orders', 'work_order_items', 'stock_mill_dispatches', 'receive_stock_mill_dispatches',
        'department_returns', 'department_return_requests', 'purchase_items', 'purchase_order_items',
    ];

    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly DatabaseManager $database,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @param array<string, mixed> $attributes */
    public function save(FabricFaultReason $reason, array $attributes): FabricFaultReason
    {
        $creating = ! $reason->exists;
        $before = $creating ? null : $this->snapshot($reason);
        $processId = (int) $attributes['process_id'];
        $text = trim((string) $attributes['reason']);
        $companyId = $this->organization->companyId();

        if (! ProcessItem::query()->where('company_id', $companyId)->whereKey($processId)->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['process_id' => 'Select an active canonical Process.']);
        }

        $duplicate = FabricFaultReason::query()->where('company_id', $companyId)->where('status', '!=', 'Deleted')
            ->where('process_id', $processId)->whereRaw('LOWER(TRIM(reason)) = ?', [strtolower($text)])
            ->when($reason->exists, fn ($query) => $query->whereKey('!=', $reason->getKey()))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['reason' => 'This reason already exists for the selected Process.']);
        }

        if ($reason->exists && $this->isReferenced($reason)) {
            foreach (['process_id', 'reason'] as $field) {
                if ((string) $reason->getRawOriginal($field) !== (string) ($field === 'process_id' ? $processId : $text)) {
                    throw ValidationException::withMessages([$field => 'Referenced reasons cannot change identity or Process; deactivate them instead.']);
                }
            }
        }

        $reason->fill(['company_id' => $companyId, 'process_id' => $processId, 'reason' => $text, 'status' => $attributes['status']]);
        $reason->modified_at = now();
        if ($creating) {
            $reason->created_at = now();
        }
        $reason->save();
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'fabric_fault_reason_created' : 'fabric_fault_reason_updated',
            'description' => 'Rejection / Wastage Reason master saved.', 'auditable_type' => $reason->getMorphClass(),
            'auditable_id' => $reason->getKey(), 'old_values' => $before, 'new_values' => $this->snapshot($reason->fresh()),
        ]);

        return $reason->fresh();
    }

    public function transition(FabricFaultReason $reason, string $status): void
    {
        $before = ['status' => $reason->getRawOriginal('status')];
        $reason->update(['status' => $status, 'modified_at' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'masters', 'action' => strtolower($status), 'event' => 'fabric_fault_reason_'.strtolower($status),
            'description' => 'Rejection / Wastage Reason status changed.', 'auditable_type' => $reason->getMorphClass(),
            'auditable_id' => $reason->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status],
        ]);
    }

    public function validateProcessRelation(int $processId, int $reasonId, bool $activeOnly = true): FabricFaultReason
    {
        $query = FabricFaultReason::query()->whereKey($reasonId)->where('process_id', $processId)->where('status', '!=', 'Deleted');
        if ($activeOnly) {
            $query->where('status', 'Active');
        }
        $reason = $query->first();
        if (! $reason) {
            throw ValidationException::withMessages(['reason_id' => 'The selected Reason is not valid for the selected Process.']);
        }
        return $reason;
    }

    public function rejectDeletion(FabricFaultReason $reason): never
    {
        throw ValidationException::withMessages(['reason' => $this->isReferenced($reason)
            ? 'Referenced reasons cannot be deleted; deactivate them instead.'
            : 'Reason identities are retained for history; deactivate them instead.']);
    }

    public function isReferenced(FabricFaultReason $reason): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            foreach (['fabric_fault_reason_id', 'fault_reason_id', 'rejection_reason_id', 'wastage_reason_id', 'reason_id'] as $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && $this->database->table($table)->where($column, $reason->getKey())->exists()) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @return array<string, mixed> */
    public function snapshot(FabricFaultReason $reason): array
    {
        return $reason->only(['id', 'process_id', 'reason', 'financial_year', 'status']);
    }
}
