<?php

namespace App\Services;

use App\Models\Factory;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WarehouseMasterService
{
    /** Operational tables whose warehouse references make deletion or relocation unsafe. */
    private const REFERENCE_TABLES = [
        'warehouse_compartments', 'warehouse_in_items', 'warehouse_out_items',
        'warehouse_items', 'warehouse_item_stocks', 'warehouse_balance_items',
        'purchase_items', 'work_inspections', 'work_inspection_details',
        'stock_mill_dispatches', 'receive_stock_mill_dispatches',
    ];

    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly AuditLogger $audit,
    ) {
    }

    public function save(Warehouse $warehouse, array $attributes): Warehouse
    {
        $creating = ! $warehouse->exists;
        $before = $warehouse->exists ? $this->snapshot($warehouse) : null;
        $factoryId = $attributes['factory_id'] ?? null;

        if ($factoryId !== null && ! Factory::query()
            ->whereKey($factoryId)
            ->where('company_id', $this->organization->companyId())
            ->where('status', 'Active')
            ->exists()) {
            throw ValidationException::withMessages(['factory_id' => 'The selected factory is not available.']);
        }

        $name = trim((string) ($attributes['warehouse_name'] ?? ''));
        $duplicate = Warehouse::query()
            ->where('company_id', $this->organization->companyId())
            ->where('warehouse_name', $name)
            ->when($factoryId === null, fn ($query) => $query->whereNull('factory_id'), fn ($query) => $query->where('factory_id', $factoryId))
            ->where('status', '!=', 'Deleted')
            ->when($warehouse->exists, fn ($query) => $query->where('warehouses.id', '!=', $warehouse->getKey()))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['warehouse_name' => 'This warehouse already exists at the selected location.']);
        }

        if ($warehouse->exists && (int) $warehouse->factory_id !== (int) $factoryId && $this->hasOperationalReferences($warehouse->id)) {
            throw ValidationException::withMessages(['factory_id' => 'A warehouse with operational history cannot be moved to another factory.']);
        }

        DB::transaction(function () use ($warehouse, $attributes, $name, $factoryId): void {
            $warehouse->fill([
                'warehouse_name' => $name,
                'location' => $this->nullableString($attributes['location'] ?? null),
                'capacity' => $attributes['capacity'] ?? null,
                'supervisor_id' => $attributes['supervisor_id'] ?? null,
                'contact_number' => $this->nullableString($attributes['contact_number'] ?? null),
                'process_type_id' => (int) ($attributes['process_type_id'] ?? 0),
                'factory_id' => $factoryId,
                'status' => $attributes['status'],
            ]);
            $warehouse->company_id = $this->organization->companyId();
            $warehouse->financial_year = $warehouse->financial_year ?: currentFinancialYear();
            $warehouse->created_at = $warehouse->created_at ?: now();
            $warehouse->updated_at = now();
            $warehouse->save();
        });

        $after = $this->snapshot($warehouse->fresh());
        $this->audit->recordAfterCommit([
            'module' => 'warehouse',
            'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'warehouse_created' : 'warehouse_updated',
            'auditable_type' => $warehouse->getMorphClass(),
            'auditable_id' => $warehouse->id,
            'description' => 'Warehouse master saved.',
            'old_values' => $before,
            'new_values' => $after,
        ]);

        return $warehouse->fresh();
    }

    public function transition(Warehouse $warehouse, string $status): void
    {
        $before = ['status' => $warehouse->getRawOriginal('status')];
        $warehouse->update(['status' => $status, 'updated_at' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'warehouse', 'action' => strtolower($status),
            'event' => 'warehouse_'.strtolower($status),
            'auditable_type' => $warehouse->getMorphClass(), 'auditable_id' => $warehouse->id,
            'description' => 'Warehouse status changed.', 'old_values' => $before,
            'new_values' => ['status' => $status],
        ]);
    }

    public function ensureNotDeletable(Warehouse $warehouse): never
    {
        throw ValidationException::withMessages(['warehouse' => 'Warehouses cannot be deleted; deactivate the warehouse instead. Historical references are preserved.']);
    }

    public function hasOperationalReferences(int $warehouseId): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            if (DB::getSchemaBuilder()->hasTable($table) && DB::getSchemaBuilder()->hasColumn($table, 'warehouse_id') && DB::table($table)->where('warehouse_id', $warehouseId)->exists()) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function snapshot(Model $warehouse): array
    {
        return $warehouse->only(['warehouse_name', 'location', 'capacity', 'supervisor_id', 'contact_number', 'process_type_id', 'company_id', 'factory_id', 'status']);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
