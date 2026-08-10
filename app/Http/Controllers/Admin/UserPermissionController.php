<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\CurrentOrganizationContext;
use App\Services\UserPermissionManagementService;
use App\Support\FrontendPermissionCatalog;
use Illuminate\Http\Request;

final class UserPermissionController extends Controller
{
    public function index(User $user, CurrentOrganizationContext $organization)
    {
        $this->assertFrontendUser($user);
        $assignments = $user->roleAssignments()->where('company_id', $organization->companyId())->where('status', 'Active')->with('role.permissions')->get();
        $overrides = $user->permissionOverrides()->where('status', 'Active')->with('permission')->get();
        $permissions = Permission::query()->whereIn('permission_key', FrontendPermissionCatalog::keys())->where('status', 'Active')->orderBy('resource')->orderBy('action')->get()->groupBy('resource');
        $roles = $assignments->pluck('role')->filter()->unique('id');
        $roleKeys = $roles->flatMap(fn ($role) => $role->permissions->pluck('permission_key'))->unique()->values();
        $overrides = $overrides->keyBy(fn ($override) => $override->permission->permission_key);
        $effective = $roleKeys->merge($overrides->where('effect', 'Allow')->pluck('permission.permission_key'))->diff($overrides->where('effect', 'Deny')->pluck('permission.permission_key'))->unique();

        return view('admin.user-permissions.index', compact('user', 'permissions', 'roles', 'roleKeys', 'overrides', 'effective'));
    }

    public function update(Request $request, User $user, UserPermissionManagementService $service)
    {
        $this->assertFrontendUser($user);
        $data = $request->validate(['permissions' => ['array'], 'permissions.*' => ['in:Allow,Deny']]);
        $service->save($user, $data['permissions'] ?? []);

        return back()->with('message', 'User permissions updated.');
    }

    private function assertFrontendUser(User $user): void
    {
        abort_unless($user->user_type === 'User' && $user->status === 'Active', 404);
    }
}
