<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Models\UserRoleAssignment;
use Illuminate\Validation\ValidationException;

final class RoleManagementService
{
    public function saveRole(array $data, ?Role $role = null): Role
    {
        $wasExisting = $role?->exists;
        $oldPermissions = $role?->permissions()->pluck('permission_key')->all() ?? [];
        $context = app(CurrentOrganizationContext::class);
        $companyId = $context->companyId();
        if ($role && $role->scope !== 'Company') {
            throw ValidationException::withMessages(['role' => 'The reserved system role cannot be edited.']);
        }
        $role ??= new Role;
        $role->fill(['company_id' => $companyId, 'role_key' => $data['role_key'] ?? str()->slug($data['name']), 'name' => $data['name'], 'scope' => 'Company', 'panel' => $data['panel'] ?? ($role->panel ?? 'Admin'), 'description' => $data['description'] ?? null, 'status' => $data['status'] ?? 'Active', 'updated_by' => auth()->id()]);
        if (! $role->exists) {
            $role->created_by = auth()->id();
        }
        $role->save();
        $permissionKeys = array_values(array_unique($data['permissions'] ?? []));
        $allowed = Permission::query()->where('status', 'Active')->whereIn('permission_key', $permissionKeys)->pluck('permission_key')->all();
        if (count($allowed) !== count($permissionKeys)) {
            throw ValidationException::withMessages(['permissions' => 'One or more permissions are invalid or inactive.']);
        }
        if (! app(AuthorizationService::class)->isSuperAdmin()) {
            $granted = app(AuthorizationService::class)->permissions();
            if (array_diff($allowed, $granted) !== []) {
                throw ValidationException::withMessages(['permissions' => 'A role manager cannot grant permissions they do not possess.']);
            }
        }
        $role->permissions()->sync(Permission::whereIn('permission_key', $allowed)->pluck('id')->mapWithKeys(fn ($id) => [$id => ['assigned_by' => auth()->id()]])->all());
        app(AuthorizationService::class)->forget();
        app(AuditLogger::class)->record([
            'module' => 'roles', 'action' => $wasExisting ? 'update' : 'create', 'event' => 'role_saved',
            'auditable_type' => $role->getMorphClass(), 'auditable_id' => $role->id,
            'description' => 'Role and permissions changed.',
            'old_values' => ['permissions' => $oldPermissions], 'new_values' => ['permissions' => $allowed],
        ]);

        return $role;
    }

    public function assign(User $user, Role $role): UserRoleAssignment
    {
        $companyId = app(CurrentOrganizationContext::class)->companyId();
        if ($role->scope !== 'Company') {
            throw ValidationException::withMessages(['role' => 'The reserved system role cannot be assigned here.']);
        }
        $expectedPanel = $user->user_type === 'Admin' ? 'Admin' : 'Frontend';
        if ($role->panel !== $expectedPanel) {
            throw ValidationException::withMessages(['role' => 'This role belongs to the other login panel.']);
        }
        if (! UserOrganizationAccess::query()->where('user_id', $user->id)->where('company_id', $companyId)->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['user' => 'The user has no active access to this company.']);
        }
        if (! app(AuthorizationService::class)->can('roles.assign')) {
            throw ValidationException::withMessages(['role' => 'Role assignment is not permitted.']);
        }

        $assignment = UserRoleAssignment::firstOrCreate(['principal_type' => $user->user_type, 'principal_id' => $user->id, 'role_id' => $role->id], ['company_id' => $companyId, 'status' => 'Active', 'assigned_by' => auth()->id()]);
        app(AuthorizationService::class)->forget();
        app(AuditLogger::class)->record(['module' => 'roles', 'action' => 'assign', 'event' => 'role_assigned', 'auditable_type' => $user->getMorphClass(), 'auditable_id' => $user->id, 'description' => 'Role assigned to principal.', 'new_values' => ['role_id' => $role->id, 'principal_type' => $user->user_type]]);

        return $assignment;
    }

    public function revoke(UserRoleAssignment $assignment): void
    {
        if ($assignment->role?->scope !== 'Company') {
            abort(403);
        }
        $assignment->update(['status' => 'Inactive', 'revoked_by' => auth()->id(), 'revoked_at' => now()]);
        app(AuthorizationService::class)->forget();
        app(AuditLogger::class)->record(['module' => 'roles', 'action' => 'revoke', 'event' => 'role_revoked', 'auditable_type' => User::class, 'auditable_id' => $assignment->principal_id, 'description' => 'Role assignment revoked.', 'old_values' => ['role_id' => $assignment->role_id, 'principal_type' => $assignment->principal_type], 'new_values' => ['status' => 'Inactive']]);
    }
}
