<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Individual;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserManagementService
{
    public function __construct(
        private readonly CurrentOrganizationContext $organization,
        private readonly RoleManagementService $roles,
        private readonly AuthorizationService $authorization,
        private readonly AuditLogger $audit,
    ) {
    }

    public function create(array $data): User
    {
        $this->assertAdmin('users.create');
        $companyId = $this->organization->companyId();
        $this->assertIndividual($data['individual_id'] ?? null);
        $roleIds = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
        $roles = $this->frontendRoles($roleIds, $companyId);
        $this->assertOrganizationScope($data, $companyId);

        return DB::transaction(function () use ($data, $companyId, $roles): User {
            $user = new User();
            $user->forceFill([
                'user_type' => 'User', 'individual_id' => $data['individual_id'] ?? null,
                'name' => $data['name'], 'email' => $data['email'],
                'password' => Hash::make($data['password']), 'status' => $data['status'],
                'financial_year' => $data['financial_year'] ?? null,
                'created_by' => auth('admin')->id(), 'modified_by' => auth('admin')->id(),
            ])->save();

            $access = UserOrganizationAccess::create([
                'user_id' => $user->id, 'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null, 'factory_id' => $data['factory_id'] ?? null,
                'department_id' => $data['department_id'] ?? null, 'is_default' => true,
                'status' => 'Active', 'created_by' => auth('admin')->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($roles as $role) {
                $this->roles->assign($user, $role);
            }
            $this->audit->record([
                'module' => 'users', 'action' => 'create', 'event' => 'frontend_user_created',
                'auditable_type' => $user->getMorphClass(), 'auditable_id' => $user->id,
                'description' => 'Frontend User account created.',
                'new_values' => ['name' => $user->name, 'email' => $user->email, 'status' => $user->status, 'role_ids' => $roles->modelKeys(), 'organization_access_id' => $access->id],
            ]);

            return $user->fresh();
        });
    }

    public function update(User $user, array $data): User
    {
        $this->assertFrontendUser($user);
        $this->assertAdmin('users.update');
        $companyId = $this->organization->companyId();
        $this->assertCompanyAccess($user, $companyId);
        $this->assertIndividual($data['individual_id'] ?? null);
        $this->assertOrganizationScope($data, $companyId);
        $old = $user->only(['name', 'email', 'individual_id', 'status']);

        return DB::transaction(function () use ($user, $data, $companyId, $old): User {
            $user->forceFill([
                'user_type' => 'User', 'name' => $data['name'], 'email' => $data['email'],
                'individual_id' => $data['individual_id'] ?? null, 'status' => $data['status'],
                'modified_by' => auth('admin')->id(), 'updated_at' => now(),
            ])->save();
            UserOrganizationAccess::query()->where('user_id', $user->id)->where('company_id', $companyId)->update([
                'branch_id' => $data['branch_id'] ?? null, 'factory_id' => $data['factory_id'] ?? null,
                'department_id' => $data['department_id'] ?? null, 'updated_at' => now(),
            ]);

            if (array_key_exists('role_ids', $data)) {
                $this->syncRoles($user, $data['role_ids']);
            }
            $this->audit->record([
                'module' => 'users', 'action' => 'update', 'event' => 'frontend_user_profile_changed',
                'auditable_type' => $user->getMorphClass(), 'auditable_id' => $user->id,
                'description' => 'Frontend User profile changed.', 'old_values' => $old,
                'new_values' => $user->only(['name', 'email', 'individual_id', 'status']),
            ]);

            return $user->fresh();
        });
    }

    public function setStatus(User $user, string $status): User
    {
        $this->assertFrontendUser($user);
        $this->assertAdmin($status === 'Active' ? 'users.activate' : 'users.deactivate');
        $this->assertCompanyAccess($user, $this->organization->companyId());
        if ($status === 'Inactive' && $user->id === auth('admin')->id()) {
            throw ValidationException::withMessages(['user' => 'You cannot deactivate your own account.']);
        }
        $old = $user->status;
        $user->forceFill(['status' => $status, 'modified_by' => auth('admin')->id(), 'updated_at' => now()])->save();
        $this->audit->record([
            'module' => 'users', 'action' => strtolower($status), 'event' => 'frontend_user_status_changed',
            'auditable_type' => $user->getMorphClass(), 'auditable_id' => $user->id,
            'description' => 'Frontend User account status changed.', 'old_values' => ['status' => $old], 'new_values' => ['status' => $status],
        ]);

        return $user->fresh();
    }

    public function resetPassword(User $user, string $password): void
    {
        $this->assertFrontendUser($user);
        $this->assertAdmin('users.reset-password');
        $this->assertCompanyAccess($user, $this->organization->companyId());
        $user->forceFill(['password' => Hash::make($password), 'modified_by' => auth('admin')->id(), 'updated_at' => now()])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $this->audit->record([
            'module' => 'users', 'action' => 'reset-password', 'event' => 'frontend_user_password_reset',
            'auditable_type' => $user->getMorphClass(), 'auditable_id' => $user->id,
            'description' => 'Frontend User password administratively reset. Password value is intentionally excluded.',
        ]);
    }

    /** @return Collection<int, Role> */
    public function frontendRoles(array $roleIds, int $companyId): Collection
    {
        if ($roleIds === []) {
            return Role::newCollection();
        }
        if (! $this->authorization->can('roles.assign')) {
            throw ValidationException::withMessages(['role_ids' => 'Role assignment is not permitted.']);
        }
        $roles = Role::query()->whereIn('id', $roleIds)->where('company_id', $companyId)->where('scope', 'Company')->where('panel', 'Frontend')->where('status', 'Active')->get();
        if ($roles->count() !== count($roleIds)) {
            throw ValidationException::withMessages(['role_ids' => 'Only active Frontend roles from the current company may be assigned.']);
        }

        return $roles;
    }

    private function syncRoles(User $user, array $roleIds): void
    {
        $companyId = $this->organization->companyId();
        $roles = $this->frontendRoles(array_values(array_unique(array_map('intval', $roleIds))), $companyId);
        $current = UserRoleAssignment::query()->where('principal_type', 'User')->where('principal_id', $user->id)->where('company_id', $companyId)->where('status', 'Active')->with('role')->get();
        foreach ($roles as $role) {
            if (! $current->contains('role_id', $role->id)) {
                $this->roles->assign($user, $role);
            }
        }
        foreach ($current as $assignment) {
            if (! $roles->contains('id', $assignment->role_id)) {
                $this->roles->revoke($assignment);
            }
        }
    }

    private function assertAdmin(string $permission): void
    {
        if (! auth('admin')->check() || (! $this->authorization->can($permission) && ! $this->authorization->can('users.manage'))) {
            throw ValidationException::withMessages(['user' => 'This User administration action is not permitted.']);
        }
    }

    private function assertFrontendUser(User $user): void
    {
        if ($user->user_type !== 'User' || $user->status === 'Deleted') {
            throw ValidationException::withMessages(['user' => 'Only Frontend User accounts can be managed here.']);
        }
    }

    private function assertCompanyAccess(User $user, int $companyId): void
    {
        if (! UserOrganizationAccess::query()->where('user_id', $user->id)->where('company_id', $companyId)->exists()) {
            throw ValidationException::withMessages(['user' => 'The Frontend User is not assigned to the current company.']);
        }
    }

    private function assertIndividual(?int $individualId): void
    {
        if ($individualId !== null && ! Individual::query()->whereKey($individualId)->where('status', '!=', 'Deleted')->exists()) {
            throw ValidationException::withMessages(['individual_id' => 'The selected employee link is not available.']);
        }
    }

    private function assertOrganizationScope(array $data, int $companyId): void
    {
        $branchId = $data['branch_id'] ?? null;
        $factoryId = $data['factory_id'] ?? null;
        $departmentId = $data['department_id'] ?? null;
        if ($branchId !== null && ! Branch::query()->whereKey($branchId)->where('company_id', $companyId)->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'The selected branch is not available.']);
        }
        if ($factoryId !== null && ! Factory::query()->whereKey($factoryId)->where('company_id', $companyId)->where('status', 'Active')->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->exists()) {
            throw ValidationException::withMessages(['factory_id' => 'The selected factory is not available.']);
        }
        if ($departmentId !== null && ! Department::query()->whereKey($departmentId)->where('company_id', $companyId)->where('status', 'Active')->when($factoryId, fn ($query) => $query->where('factory_id', $factoryId))->exists()) {
            throw ValidationException::withMessages(['department_id' => 'The selected department is not available.']);
        }
    }
}
