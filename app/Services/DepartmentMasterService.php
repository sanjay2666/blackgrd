<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class DepartmentMasterService
{
    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly AuditLogger $audit,
    ) {
    }

    public function save(Department $department, array $attributes): Department
    {
        $creating = ! $department->exists;
        $before = $department->exists ? $this->snapshot($department) : null;
        $factoryId = $attributes['factory_id'] ?? null;

        if ($factoryId !== null && ! Factory::query()
            ->whereKey($factoryId)
            ->where('company_id', $this->organization->companyId())
            ->where('status', 'Active')
            ->exists()) {
            throw ValidationException::withMessages(['factory_id' => 'The selected factory is not available.']);
        }

        $name = trim((string) ($attributes['department_name'] ?? ''));
        $duplicate = Department::query()
            ->where('company_id', $this->organization->companyId())
            ->where('department_name', $name)
            ->when($factoryId === null, fn ($query) => $query->whereNull('factory_id'), fn ($query) => $query->where('factory_id', $factoryId))
            ->where('status', '!=', 'Deleted')
            ->when($department->exists, fn ($query) => $query->where('departments.id', '!=', $department->getKey()))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['department_name' => 'This department already exists at the selected location.']);
        }

        $department->fill([
            'department_name' => $name,
            'factory_id' => $factoryId,
            'financial_year' => $attributes['financial_year'] ?? $department->financial_year,
            'status' => $attributes['status'],
        ]);
        $department->company_id = $this->organization->companyId();
        $department->financial_year = $department->financial_year ?: currentFinancialYear();
        $department->created_by = $department->created_by ?: Auth::guard('admin')->id();
        $department->modified_by = $department->exists ? Auth::guard('admin')->id() : null;
        $department->updated_at = now();
        $department->created_at = $department->created_at ?: now();
        $department->save();

        $after = $this->snapshot($department->fresh());
        $this->audit->recordAfterCommit([
            'module' => 'departments',
            'action' => $creating ? 'create' : 'update',
            'event' => $creating ? 'department_created' : 'department_updated',
            'auditable_type' => $department->getMorphClass(),
            'auditable_id' => $department->id,
            'description' => 'Department master saved.',
            'old_values' => $before,
            'new_values' => $after,
        ]);

        return $department->fresh();
    }

    public function transition(Department $department, string $status): void
    {
        $before = ['status' => $department->getRawOriginal('status')];
        $department->update(['status' => $status]);
        $this->audit->recordAfterCommit([
            'module' => 'departments',
            'action' => strtolower($status),
            'event' => 'department_'.strtolower($status),
            'auditable_type' => $department->getMorphClass(),
            'auditable_id' => $department->id,
            'description' => 'Department status changed.',
            'old_values' => $before,
            'new_values' => ['status' => $status],
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshot(Model $department): array
    {
        return $department->only(['department_name', 'factory_id', 'financial_year', 'status']);
    }
}
