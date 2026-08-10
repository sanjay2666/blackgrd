<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\RoleManagementService;
use App\Support\PermissionRegistry;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->where('scope', 'Company')->where('status', '!=', 'Deleted')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::where('status', 'Active')->whereIn('permission_key', PermissionRegistry::companyAdminAssignable())->orderBy('resource')->orderBy('action')->get()->groupBy('resource');

        return view('admin.roles.form', ['role' => new Role, 'permissions' => $permissions]);
    }

    public function store(Request $request, RoleManagementService $service)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string'], 'status' => ['required', 'in:Active,Inactive'], 'permissions' => ['array'], 'permissions.*' => ['string', 'max:120']]);
        $service->saveRole($data);

        return redirect()->route('admin.roles.index')->with('message', 'Role saved.');
    }

    public function edit(Role $role)
    {
        $this->assertEditableRole($role);
        $permissions = Permission::where('status', 'Active')->whereIn('permission_key', PermissionRegistry::companyAdminAssignable())->orderBy('resource')->orderBy('action')->get()->groupBy('resource');

        return view('admin.roles.form', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role, RoleManagementService $service)
    {
        $this->assertEditableRole($role);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string'], 'status' => ['required', 'in:Active,Inactive'], 'permissions' => ['array'], 'permissions.*' => ['string', 'max:120']]);
        $service->saveRole($data, $role);

        return redirect()->route('admin.roles.index')->with('message', 'Role updated.');
    }

    public function assignForm(Role $role)
    {
        $this->assertEditableRole($role);
        $userType = $role->panel === 'Admin' ? 'Admin' : 'User';
        $users = User::where('user_type', $userType)->whereHas('organizationAccess', fn ($q) => $q->where('status', 'Active'))->orderBy('name')->get();

        return view('admin.roles.assign', compact('role', 'users'));
    }

    public function assign(Request $request, Role $role, RoleManagementService $service)
    {
        $this->assertEditableRole($role);
        $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $service->assign(User::findOrFail($request->integer('user_id')), $role);

        return back()->with('message', 'Role assigned.');
    }

    public function revoke(UserRoleAssignment $assignment, RoleManagementService $service)
    {
        $service->revoke($assignment);

        return back()->with('message', 'Role revoked.');
    }

    private function assertEditableRole(Role $role): void
    {
        if ($role->scope !== 'Company') {
            app(\App\Services\AuditLogger::class)->record(['module' => 'security', 'action' => 'deny', 'event' => 'reserved_role_ui_access_attempt', 'auditable_type' => $role->getMorphClass(), 'auditable_id' => $role->id, 'description' => 'Attempt to access the reserved Super Admin role through Company Admin UI.']);
        }
        abort_unless($role->scope === 'Company', 404);
    }
}
