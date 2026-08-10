<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Models\UserPermissionOverride;
use App\Support\FrontendPermissionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UserPermissionManagementService
{
    public function save(User $user, array $effects): void
    {
        $this->assertFrontendUser($user);
        if (! auth('admin')->check()) {
            throw ValidationException::withMessages(['user' => 'Only an authenticated Admin may customize Frontend User permissions.']);
        }
        $companyId = app(CurrentOrganizationContext::class)->companyId();
        if (! UserOrganizationAccess::query()->where('user_id', $user->id)->where('company_id', $companyId)->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['user' => 'The Frontend User has no active access to this company.']);
        }
        if (! app(AuthorizationService::class)->can('users.manage')) {
            throw ValidationException::withMessages(['user' => 'Individual permission management is not permitted.']);
        }
        $allowedKeys = FrontendPermissionCatalog::keys();
        $requested = array_filter($effects, static fn ($effect): bool => in_array($effect, ['Allow', 'Deny'], true));
        $unknown = array_diff(array_keys($requested), $allowedKeys);
        if ($unknown !== []) {
            throw ValidationException::withMessages(['permissions' => 'One or more permissions are not assignable to Frontend Users.']);
        }
        $managerPermissions = app(AuthorizationService::class)->permissions();
        if (array_diff(array_keys($requested), $managerPermissions) !== []) {
            throw ValidationException::withMessages(['permissions' => 'A manager cannot customize permissions they do not possess.']);
        }
        $permissionIds = Permission::query()->where('status', 'Active')->whereIn('permission_key', array_keys($requested))->pluck('id', 'permission_key');
        if ($permissionIds->count() !== count($requested)) {
            throw ValidationException::withMessages(['permissions' => 'One or more permissions are inactive or unavailable.']);
        }

        $before = UserPermissionOverride::query()->where('user_id', $user->id)->where('status', 'Active')->with('permission')->get()->mapWithKeys(fn (UserPermissionOverride $override): array => [$override->permission->permission_key => $override->effect])->all();
        DB::transaction(function () use ($user, $requested, $permissionIds): void {
            foreach ($requested as $key => $effect) {
                UserPermissionOverride::updateOrCreate(
                    ['user_id' => $user->id, 'permission_id' => $permissionIds[$key]],
                    ['effect' => $effect, 'status' => 'Active', 'assigned_by' => auth('admin')->id(), 'revoked_by' => null, 'revoked_at' => null]
                );
            }
            UserPermissionOverride::query()->where('user_id', $user->id)->whereNotIn('permission_id', $permissionIds->values())->update(['status' => 'Inactive', 'revoked_by' => auth('admin')->id(), 'revoked_at' => now()]);
        });
        app(AuthorizationService::class)->forget();
        app(AuditLogger::class)->record(['module' => 'users', 'action' => 'manage', 'event' => 'user_permission_overrides_changed', 'auditable_type' => $user->getMorphClass(), 'auditable_id' => $user->id, 'description' => 'Individual Frontend User permission overrides changed.', 'old_values' => ['permissions' => $before], 'new_values' => ['permissions' => $requested]]);
    }

    private function assertFrontendUser(User $user): void
    {
        if ($user->user_type !== 'User' || $user->status !== 'Active') {
            throw ValidationException::withMessages(['user' => 'Only active Frontend Users can be customized.']);
        }
    }
}
