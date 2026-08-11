<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Machine;
use App\Models\ProcessItem;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class MachineMasterService
{
    private const REFERENCE_TABLES = ['work_orders', 'work_process_requirements', 'work_inspections', 'work_inspection_details', 'warehouse_balance_items', 'warehouse_in_items', 'warehouse_item_stocks', 'warehouse_out_items'];

    public function __construct(private readonly CurrentOrganizationContext $organization, private readonly DatabaseManager $database, private readonly AuditLogger $audit)
    {
    }

    public function save(Machine $machine, array $attributes): Machine
    {
        $creating = ! $machine->exists;
        $before = $creating ? null : $this->snapshot($machine);
        $process = ProcessItem::query()->active()->whereKey($attributes['process_wise'] ?? null)->first();
        if (! $process) {
            throw ValidationException::withMessages(['process_wise' => 'Please select a valid active Process.']);
        }
        $factoryId = $attributes['factory_id'] ?? null;
        $departmentId = $attributes['department_id'] ?? null;
        $this->assertLocation($factoryId, $departmentId);
        $name = trim((string) ($attributes['name'] ?? ''));
        $duplicate = Machine::query()->where('company_id', $this->organization->companyId())->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])->where('process_wise', $process->getKey())
            ->when($factoryId === null, fn ($q) => $q->whereNull('factory_id'), fn ($q) => $q->where('factory_id', $factoryId))
            ->when($departmentId === null, fn ($q) => $q->whereNull('department_id'), fn ($q) => $q->where('department_id', $departmentId))
            ->where('status', '!=', RecordStatus::Deleted->value)->when($machine->exists, fn ($q) => $q->where('id', '!=', $machine->getKey()))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This Machine already exists for the selected process and location.']);
        }
        if ($machine->exists && $this->isReferenced($machine)) {
            foreach (['process_wise' => 'Process', 'factory_id' => 'Factory', 'department_id' => 'Department'] as $field => $label) {
                if ((string) ($machine->{$field} ?? '') !== (string) ($attributes[$field] ?? '')) {
                    throw ValidationException::withMessages([$field => "Referenced Machine {$label} cannot be changed because it would alter historical meaning."]);
                }
            }
        }
        $machine->fill(['name' => $name, 'process_wise' => $process->getKey(), 'is_busy' => $attributes['is_busy'] ?? '0', 'factory_id' => $factoryId, 'department_id' => $departmentId, 'status' => $attributes['status']]);
        $machine->company_id = $this->organization->companyId();
        $machine->created_by = $machine->created_by ?: auth('admin')->id();
        $machine->modified_by = auth('admin')->id();
        $machine->created = $machine->created ?: now();
        $machine->created_at = $machine->created_at ?: now();
        $machine->modified = now();
        $machine->updated_at = now();
        $machine->save();
        $this->audit->recordAfterCommit(['module' => 'masters', 'action' => $creating ? 'create' : 'update', 'event' => $creating ? 'machine_created' : 'machine_updated', 'description' => 'Machine master saved.', 'auditable_type' => $machine->getMorphClass(), 'auditable_id' => $machine->getKey(), 'old_values' => $before, 'new_values' => $this->snapshot($machine->fresh())]);

        return $machine->fresh();
    }

    public function transition(Machine $machine, string $status): void
    {
        if ($status === RecordStatus::Inactive->value && $this->hasActiveWork($machine)) {
            throw ValidationException::withMessages(['status' => 'This Machine has active Work Orders or planned Dyeing lots and cannot be deactivated.']);
        }
        $before = ['status' => $machine->getRawOriginal('status')];
        $machine->status = $status;
        $machine->modified = now();
        $machine->modified_by = auth('admin')->id();
        $machine->save();
        $this->audit->recordAfterCommit(['module' => 'masters', 'action' => strtolower($status), 'event' => 'machine_'.strtolower($status), 'description' => 'Machine status changed.', 'auditable_type' => $machine->getMorphClass(), 'auditable_id' => $machine->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status]]);
    }

    public function isReferenced(Machine $machine): bool
    {
        foreach (self::REFERENCE_TABLES as $table) {
            foreach (['machine_id', 'dyeing_machine_id'] as $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && $this->database->table($table)->where($column, $machine->getKey())->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasActiveWork(Machine $machine): bool
    {
        foreach (['work_orders' => 'machine_id', 'work_process_requirements' => 'dyeing_machine_id'] as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && $this->database->table($table)->where($column, $machine->getKey())->where('status', '!=', RecordStatus::Deleted->value)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function assertLocation(?int $factoryId, ?int $departmentId): void
    {
        if ($factoryId !== null && ! Factory::query()->whereKey($factoryId)->where('company_id', $this->organization->companyId())->active()->exists()) {
            throw ValidationException::withMessages(['factory_id' => 'The selected factory is not available.']);
        }
        if ($departmentId !== null) {
            $department = Department::query()->whereKey($departmentId)->where('company_id', $this->organization->companyId())->active()->first();
            if (! $department || ($factoryId !== null && $department->factory_id !== null && (int) $department->factory_id !== $factoryId)) {
                throw ValidationException::withMessages(['department_id' => 'The selected department is not available for this factory.']);
            }
        }
    }

    private function snapshot(Model $machine): array
    {
        return $machine->only(['id', 'name', 'process_wise', 'factory_id', 'department_id', 'is_busy', 'status']);
    }
}
