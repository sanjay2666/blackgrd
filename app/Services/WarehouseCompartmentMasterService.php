<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\Warehouse;
use App\Models\WarehouseCompartment;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class WarehouseCompartmentMasterService
{
    /** @var list<string> */
    private const REFERENCE_TABLES = [
        'warehouse_in_items', 'warehouse_out_items', 'warehouse_balance_items',
        'warehouse_item_stocks', 'purchase_items',
    ];

    public function __construct(
        private readonly DatabaseManager $database,
        private readonly CurrentOrganizationContext $organization,
        private readonly AuditLogger $audit,
    ) {
    }

    public function query(): Builder
    {
        return WarehouseCompartment::query()
            ->whereHas('warehouse', fn (Builder $query) => $query->where('company_id', $this->organization->companyId()))
            ->with(['warehouse.factory'])
            ->notDeleted();
    }

    public function findForCurrentCompany(int $id): WarehouseCompartment
    {
        return $this->query()->whereKey($id)->firstOrFail();
    }

    /** @return Collection<int, Warehouse> */
    public function availableWarehouses(?int $includeId = null): Collection
    {
        return Warehouse::query()->where(function (Builder $query) use ($includeId): void {
            $query->where('status', RecordStatus::Active->value);
            if ($includeId !== null) {
                $query->orWhereKey($includeId);
            }
        })->orderBy('warehouse_name')->get();
    }

    public function save(WarehouseCompartment $compartment, array $attributes, Request $request): WarehouseCompartment
    {
        $creating = ! $compartment->exists;
        $before = $compartment->exists ? $this->snapshot($compartment) : null;
        $warehouse = $this->validWarehouse((int) $attributes['warehouse_id'], $compartment);
        $name = trim((string) $attributes['compartment_name']);

        if ($name === '') {
            throw ValidationException::withMessages(['compartment_name' => 'Please enter a Compartment / Bin name.']);
        }

        $duplicate = WarehouseCompartment::query()
            ->where('warehouse_id', $warehouse->getKey())
            ->whereRaw('LOWER(TRIM(compartment_name)) = ?', [mb_strtolower($name)])
            ->notDeleted()
            ->when($compartment->exists, fn (Builder $query) => $query->where($query->getModel()->getKeyName(), '!=', $compartment->getKey()))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['compartment_name' => 'This Compartment / Bin name already exists in the selected Warehouse.']);
        }

        $referenced = $compartment->exists && $this->isReferenced($compartment);
        if ($referenced && (int) $compartment->warehouse_id !== $warehouse->getKey()) {
            throw ValidationException::withMessages(['warehouse_id' => 'A referenced Compartment cannot be moved to another Warehouse because that changes historical stock-location meaning.']);
        }
        if ($referenced && strcasecmp((string) $compartment->compartment_name, $name) !== 0) {
            throw ValidationException::withMessages(['compartment_name' => 'A referenced Compartment name cannot be changed. Deactivate it and create a new Compartment instead.']);
        }
        if ($compartment->exists && $attributes['status'] === RecordStatus::Inactive->value && $this->hasCurrentStock($compartment)) {
            throw ValidationException::withMessages(['status' => 'This Compartment cannot be deactivated while active stock still references it.']);
        }

        if (! $creating && (int) $compartment->warehouse_id !== $warehouse->getKey() && $warehouse->status->value !== RecordStatus::Active->value) {
            throw ValidationException::withMessages(['warehouse_id' => 'New assignments must use an active Warehouse.']);
        }

        $this->database->transaction(function () use ($compartment, $attributes, $warehouse, $name): void {
            $compartment->fill([
                'warehouse_id' => $warehouse->getKey(),
                'compartment_name' => $name,
                'ind_emp_id' => $attributes['ind_emp_id'] ?? null,
                'status' => $attributes['status'],
                'financial_year' => $compartment->financial_year ?: currentFinancialYear(),
                'created_by' => $compartment->created_by ?: auth('admin')->id(),
                'modified_by' => auth('admin')->id(),
                'created_at' => $compartment->created_at ?: now(),
                'updated_at' => now(),
            ]);
            $compartment->save();
        });

        $fresh = $compartment->fresh(['warehouse.factory']);
        $this->audit->recordAfterCommit([
            'module' => 'warehouse',
            'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'warehouse_compartment_created' : 'warehouse_compartment_updated',
            'auditable_type' => $fresh->getMorphClass(),
            'auditable_id' => $fresh->getKey(),
            'description' => 'Warehouse Compartment / Bin master saved.',
            'old_values' => $before,
            'new_values' => $this->snapshot($fresh),
            'request' => $request,
        ]);

        return $fresh;
    }

    public function transition(WarehouseCompartment $compartment, string $status, Request $request): void
    {
        if ($status === RecordStatus::Inactive->value && $this->hasCurrentStock($compartment)) {
            throw ValidationException::withMessages(['status' => 'This Compartment cannot be deactivated while active stock still references it.']);
        }
        $before = $this->snapshot($compartment);
        $compartment->update(['status' => $status, 'modified_by' => auth('admin')->id(), 'updated_at' => now()]);
        $this->audit->recordAfterCommit([
            'module' => 'warehouse', 'action' => strtolower($status),
            'event' => 'warehouse_compartment_'.strtolower($status),
            'auditable_type' => $compartment->getMorphClass(), 'auditable_id' => $compartment->getKey(),
            'description' => 'Warehouse Compartment / Bin status changed.', 'old_values' => $before,
            'new_values' => $this->snapshot($compartment->fresh()), 'request' => $request,
        ]);
    }

    public function ensureNotDeletable(WarehouseCompartment $compartment): never
    {
        throw ValidationException::withMessages(['compartment' => 'Compartments cannot be deleted; deactivate the Compartment instead. Historical references are preserved.']);
    }

    public function isReferenced(WarehouseCompartment $compartment): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'ware_comp_id')
                && $this->database->table($table)->where('ware_comp_id', $compartment->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function hasCurrentStock(WarehouseCompartment $compartment): bool
    {
        return Schema::hasTable('warehouse_item_stocks')
            && $this->database->table('warehouse_item_stocks')->where('ware_comp_id', $compartment->getKey())->where('status', 'Active')->exists();
    }

    private function validWarehouse(int $warehouseId, WarehouseCompartment $compartment): Warehouse
    {
        $warehouse = Warehouse::query()->whereKey($warehouseId)->first();
        if (! $warehouse || $warehouse->status->value === RecordStatus::Deleted->value
            || ($warehouse->status->value !== RecordStatus::Active->value && (! $compartment->exists || (int) $compartment->warehouse_id !== $warehouseId))) {
            throw ValidationException::withMessages(['warehouse_id' => 'Please select a valid active Warehouse.']);
        }

        return $warehouse;
    }

    /** @return array<string, mixed> */
    private function snapshot(WarehouseCompartment $compartment): array
    {
        return $compartment->only(['id', 'warehouse_id', 'compartment_name', 'ind_emp_id', 'status']);
    }
}
