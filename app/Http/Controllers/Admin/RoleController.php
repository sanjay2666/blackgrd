<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\RoleManagementService;
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
        $permissions = Permission::where('status', 'Active')->orderBy('resource')->orderBy('action')->get()->groupBy('resource');

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
        $permissions = Permission::where('status', 'Active')->orderBy('resource')->orderBy('action')->get()->groupBy('resource');

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
        abort_unless($role->scope === 'Company', 404);
    }
}
