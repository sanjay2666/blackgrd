<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Individual;
use App\Models\Shift;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class EmployeeMasterService
{
    /** @var list<array{table:string,column:string}> */
    private const REFERENCES = [
        ['table' => 'users', 'column' => 'individual_id'], ['table' => 'individual_address', 'column' => 'individual_id'],
        ['table' => 'warehouse_out_items', 'column' => 'individual_id'], ['table' => 'warehouse_in_items', 'column' => 'ind_emp_id'],
        ['table' => 'warehouse_item_stocks', 'column' => 'inspected_by'], ['table' => 'work_inspections', 'column' => 'inspected_by'],
        ['table' => 'work_orders', 'column' => 'master_ind_id'], ['table' => 'work_orders', 'column' => 'process_started_by'],
        ['table' => 'work_orders', 'column' => 'process_ended_by'], ['table' => 'work_orders', 'column' => 'process_inspected_by'],
        ['table' => 'work_orders', 'column' => 'end_process_emp_id'], ['table' => 'notifications', 'column' => 'emp_id'],
        ['table' => 'warehouse_compartments', 'column' => 'ind_emp_id'], ['table' => 'receive_stock_mill_dispatches', 'column' => 'receiver_emp_ind_id'],
    ];

    public function __construct(private readonly CurrentOrganizationContext $organization, private readonly DatabaseManager $database, private readonly AuditLogger $audit)
    {
    }

    public function save(Individual $employee, array $attributes, Request $request): Individual
    {
        return DB::transaction(function () use ($employee, $attributes, $request): Individual {
            $companyId = $this->organization->companyId();
            $factoryId = $attributes['factory_id'] ?? null;
            $departmentId = $attributes['department_id'] ?? null;
            $shiftId = $attributes['shift_id'] ?? null;
            $this->assertFactory($factoryId, $companyId);
            $this->assertDepartment($departmentId, $factoryId, $companyId);
            $this->assertShift($shiftId, $factoryId, $companyId);
            $code = trim((string) ($attributes['employee_code'] ?? '')) ?: null;
            if ($code !== null && Individual::query()->where('company_id', $companyId)->where('type', 'employee')->where('status', '!=', RecordStatus::Deleted->value)->whereRaw('LOWER(TRIM(employee_code)) = ?', [strtolower($code)])->when($employee->exists, fn ($q) => $q->where('id', '!=', $employee->getKey()))->exists()) {
                throw ValidationException::withMessages(['employee_code' => 'This Employee Code already exists.']);
            }
            $employee->type = 'employee';
            $employee->company_id = $companyId;
            $before = $employee->exists ? $this->snapshot($employee) : null;
            $employee->fill(['name' => trim((string) $attributes['name']), 'employee_code' => $code, 'designation' => $attributes['designation'] ?? null, 'phone' => $attributes['phone'] ?? null, 'email' => $attributes['email'] ?? null, 'department_id' => $departmentId, 'factory_id' => $factoryId, 'shift_id' => $shiftId, 'status' => $attributes['status']]);
            $employee->created_by = $employee->created_by ?: auth('admin')->id();
            $employee->modified_by = auth('admin')->id();
            $employee->created_at = $employee->created_at ?: now();
            $employee->modified_at = now();
            $employee->save();
            $this->audit->recordAfterCommit(['module' => 'employees', 'action' => $before ? 'update' : 'create', 'event' => $before ? 'employee_updated' : 'employee_created', 'description' => 'Employee master saved.', 'auditable_type' => $employee->getMorphClass(), 'auditable_id' => $employee->getKey(), 'old_values' => $before, 'new_values' => $this->snapshot($employee->fresh()), 'request' => $request]);

            return $employee->fresh(['department', 'factory', 'shift', 'users']);
        });
    }

    public function transition(Individual $employee, string $status): void
    {
        $before = ['status' => $employee->getRawOriginal('status')];
        $employee->status = $status;
        $employee->modified_at = now();
        $employee->modified_by = auth('admin')->id();
        $employee->save();
        $this->audit->recordAfterCommit(['module' => 'employees', 'action' => strtolower($status), 'event' => 'employee_'.strtolower($status), 'description' => 'Employee status changed. Linked login accounts are managed separately.', 'auditable_type' => $employee->getMorphClass(), 'auditable_id' => $employee->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status]]);
    }

    public function remove(Individual $employee, Request $request): void
    {
        if ($this->isReferenced($employee)) {
            throw ValidationException::withMessages(['employee' => 'Referenced Employees cannot be deleted; deactivate the Employee instead.']);
        }
        $before = $this->snapshot($employee);
        $employee->status = RecordStatus::Deleted->value;
        $employee->deleted_at = now();
        $employee->modified_at = now();
        $employee->modified_by = auth('admin')->id();
        $employee->save();
        $this->audit->recordAfterCommit(['module' => 'employees', 'action' => 'delete', 'event' => 'employee_deleted', 'description' => 'Employee removed.', 'auditable_type' => $employee->getMorphClass(), 'auditable_id' => $employee->getKey(), 'old_values' => $before, 'new_values' => $this->snapshot($employee), 'request' => $request]);
    }

    public function isReferenced(Individual $employee): bool
    {
        foreach (self::REFERENCES as $reference) {
            if (Schema::hasTable($reference['table']) && Schema::hasColumn($reference['table'], $reference['column']) && $this->database->table($reference['table'])->where($reference['column'], $employee->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function assertFactory(?int $factoryId, int $companyId): void
    {
        if ($factoryId !== null && ! Factory::query()->whereKey($factoryId)->where('company_id', $companyId)->active()->exists()) {
            throw ValidationException::withMessages(['factory_id' => 'The selected Factory is not available.']);
        }
    }

    private function assertDepartment(?int $departmentId, ?int $factoryId, int $companyId): void
    {
        if ($departmentId === null) {
            return;
        }
        $department = Department::query()->whereKey($departmentId)->where('company_id', $companyId)->active()->first();
        if (! $department || ($department->factory_id !== null && (int) $department->factory_id !== (int) $factoryId)) {
            throw ValidationException::withMessages(['department_id' => 'The selected Department is not available for this Factory.']);
        }
    }

    private function assertShift(?int $shiftId, ?int $factoryId, int $companyId): void
    {
        if ($shiftId === null) {
            return;
        }
        if (! Schema::hasTable('shifts')) {
            throw ValidationException::withMessages(['shift_id' => 'Shift Master is not available.']);
        }
        $shift = Shift::query()->whereKey($shiftId)->where('company_id', $companyId)->active()->first();
        if (! $shift || ($shift->factory_id !== null && (int) $shift->factory_id !== (int) $factoryId)) {
            throw ValidationException::withMessages(['shift_id' => 'The selected Shift is not available for this Factory.']);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(Individual $employee): array
    {
        return $employee->only(['id', 'name', 'employee_code', 'designation', 'phone', 'email', 'department_id', 'factory_id', 'shift_id', 'type', 'status']);
    }
}
