<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\Machine;
use App\Models\MachineCapacity;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MachineCapacityService
{
    public function __construct(private readonly CurrentOrganizationContext $organization, private readonly AuditLogger $audit)
    {
    }

    public function save(MachineCapacity $capacity, array $attributes, Request $request): MachineCapacity
    {
        return DB::transaction(function () use ($capacity, $attributes, $request): MachineCapacity {
            $machine = Machine::query()->whereKey($attributes['machine_id'] ?? null)->first();
            if (! $machine || $machine->status !== RecordStatus::Active->value) {
                throw ValidationException::withMessages(['machine_id' => 'Please select a valid active Machine.']);
            }
            $unit = UnitType::query()->whereKey($attributes['unit_type_id'] ?? null)->notDeleted()->first();
            if (! $unit || $unit->status !== RecordStatus::Active->value) {
                throw ValidationException::withMessages(['unit_type_id' => 'Please select a valid active Unit.']);
            }
            $value = (float) ($attributes['capacity_value'] ?? 0);
            if ($value <= 0) {
                throw ValidationException::withMessages(['capacity_value' => 'Capacity must be greater than zero.']);
            }
            $duplicate = MachineCapacity::query()->where('company_id', $this->organization->companyId())->where('machine_id', $machine->getKey())->where('status', '!=', RecordStatus::Deleted->value)->when($capacity->exists, fn ($q) => $q->where('id', '!=', $capacity->getKey()))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['machine_id' => 'This Machine already has a capacity configuration.']);
            }
            $old = $capacity->exists ? $capacity->getAttributes() : null;
            $capacity->fill(['machine_id' => $machine->getKey(), 'unit_type_id' => $unit->getKey(), 'capacity_value' => $value, 'status' => RecordStatus::Active->value]);
            $capacity->company_id = $this->organization->companyId();
            $capacity->created_by = $capacity->created_by ?: auth('admin')->id();
            $capacity->modified_by = auth('admin')->id();
            $capacity->created = $capacity->created ?: now();
            $capacity->modified = now();
            $capacity->created_at = $capacity->created_at ?: now();
            $capacity->updated_at = now();
            $capacity->save();
            $this->audit->recordAfterCommit(['module' => 'machine-capacities', 'action' => $old ? 'update' : 'create', 'event' => $old ? 'machine_capacity_updated' : 'machine_capacity_created', 'description' => 'Machine capacity configuration saved.', 'auditable_type' => $capacity->getMorphClass(), 'auditable_id' => $capacity->getKey(), 'old_values' => $old, 'new_values' => $capacity->getAttributes(), 'request' => $request]);

            return $capacity->fresh(['machine', 'unitType']);
        });
    }

    public function remove(MachineCapacity $capacity, Request $request): void
    {
        $old = $capacity->getAttributes();
        $capacity->status = RecordStatus::Deleted->value;
        $capacity->modified = now();
        $capacity->modified_by = auth('admin')->id();
        $capacity->save();
        $this->audit->recordAfterCommit(['module' => 'machine-capacities', 'action' => 'delete', 'event' => 'machine_capacity_deleted', 'description' => 'Machine capacity configuration removed.', 'auditable_type' => $capacity->getMorphClass(), 'auditable_id' => $capacity->getKey(), 'old_values' => $old, 'new_values' => $capacity->getAttributes(), 'request' => $request]);
    }
}
