<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DepartmentAccessService
{
    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly AuthorizationService $authorization,
        private readonly AuditLogger $audit,
    ) {
    }

    /** @return list<int> */
    public function allowedDepartmentIds(?User $user = null): array
    {
        $user ??= auth('web')->user();
        if (! $user instanceof User || $user->user_type !== 'User') {
            return [];
        }

        return UserDepartmentAccess::query()->where('user_id', $user->id)
            ->where('company_id', $this->organization->companyId())->where('status', 'Active')
            ->whereHas('department', fn (Builder $q) => $q->where('status', 'Active'))
            ->pluck('department_id')->map(fn ($id) => (int) $id)->all();
    }

    public function mayAccess(int $departmentId, ?User $user = null): bool
    {
        return in_array($departmentId, $this->allowedDepartmentIds($user), true);
    }

    public function scope(Builder $query, string $column = 'department_id'): Builder
    {
        return $query->whereIn($column, $this->allowedDepartmentIds());
    }

    public function sync(User $user, array $departmentIds): void
    {
        $this->assertAdmin();
        $companyId = $this->organization->companyId();
        $departmentIds = array_values(array_unique(array_map('intval', $departmentIds)));
        $departments = Department::query()->whereIn('id', $departmentIds)->where('company_id', $companyId)
            ->where('status', 'Active')->get();
        if ($departments->count() !== count($departmentIds)) {
            throw ValidationException::withMessages(['department_ids' => 'Only active Departments from the canonical company may be assigned.']);
        }
        if (! $user->organizationAccess()->where('company_id', $companyId)->exists()) {
            throw ValidationException::withMessages(['user' => 'The Frontend User is not assigned to the current company.']);
        }
        DB::transaction(function () use ($user, $companyId, $departmentIds): void {
            $current = UserDepartmentAccess::query()->where('user_id', $user->id)->where('company_id', $companyId)->where('status', 'Active')->pluck('department_id')->map(fn ($id) => (int) $id)->all();
            $added = array_values(array_diff($departmentIds, $current));
            $removed = array_values(array_diff($current, $departmentIds));
            foreach ($added as $departmentId) {
                UserDepartmentAccess::updateOrCreate(['user_id' => $user->id, 'company_id' => $companyId, 'department_id' => $departmentId], ['status' => 'Active', 'updated_by' => auth('admin')->id()]);
            }
            if ($removed !== []) {
                UserDepartmentAccess::query()->where('user_id', $user->id)->where('company_id', $companyId)->whereIn('department_id', $removed)->update(['status' => 'Inactive', 'updated_by' => auth('admin')->id(), 'updated_at' => now()]);
            }
            $this->audit->record(['module' => 'users', 'action' => 'department-access', 'event' => 'user_department_access_changed', 'auditable_type' => $user->getMorphClass(), 'auditable_id' => $user->id, 'description' => 'Frontend User Department Access changed.', 'old_values' => ['department_ids' => $current], 'new_values' => ['department_ids' => $departmentIds]]);
        });
    }

    public function grantHomeDepartment(User $user, ?int $departmentId): void
    {
        if ($departmentId === null) {
            return;
        }
        $ids = $this->allowedDepartmentIds($user);
        if (! in_array($departmentId, $ids, true)) {
            $this->sync($user, array_merge($ids, [$departmentId]));
        }
    }

    private function assertAdmin(): void
    {
        if (! auth('admin')->check() || ! $this->authorization->can('users.manage')) {
            throw ValidationException::withMessages(['user' => 'Department Access administration is not permitted.']);
        }
    }
}
