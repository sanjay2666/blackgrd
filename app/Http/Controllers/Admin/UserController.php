<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserRequest;
use App\Http\Requests\DepartmentAccessRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Individual;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrentOrganizationContext;
use App\Services\DepartmentAccessService;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function index(Request $request, CurrentOrganizationContext $organization): View
    {
        $companyId = $organization->companyId();
        $query = User::query()->where('user_type', 'User')->where('status', '!=', 'Deleted')
            ->whereHas('organizationAccess', fn ($access) => $access->where('company_id', $companyId))
            ->with(['individual', 'roleAssignments.role', 'departmentAccess' => fn ($access) => $access->where('company_id', $companyId)->with('department'), 'organizationAccess' => fn ($access) => $access->where('company_id', $companyId)->with(['branch', 'factory', 'department'])]);
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhereHas('individual', fn ($individual) => $individual->where('phone', 'like', "%{$search}%")));
        }
        if ($request->filled('status') && in_array($request->query('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('role_id') && ctype_digit((string) $request->query('role_id'))) {
            $query->whereHas('roleAssignments', fn ($assignment) => $assignment->where('role_id', (int) $request->query('role_id'))->where('company_id', $companyId)->where('status', 'Active'));
        }
        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $roles = Role::query()->where('company_id', $companyId)->where('scope', 'Company')->where('panel', 'Frontend')->where('status', 'Active')->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(CurrentOrganizationContext $organization): View
    {
        return view('admin.users.form', $this->formData($organization->companyId()) + ['user' => new User(), 'roleIds' => []]);
    }

    public function store(AdminUserRequest $request, UserManagementService $service): RedirectResponse
    {
        $service->create($request->validated());

        return redirect()->route('admin.users.index')->with('status', 'Frontend User created successfully.');
    }

    public function edit(User $user, CurrentOrganizationContext $organization): View
    {
        $this->assertFrontendUser($user);
        $companyId = $organization->companyId();
        $access = $user->organizationAccess()->where('company_id', $companyId)->with(['branch', 'factory', 'department'])->firstOrFail();
        $roleIds = $user->roleAssignments()->where('company_id', $companyId)->where('status', 'Active')->whereHas('role', fn ($role) => $role->where('scope', 'Company')->where('panel', 'Frontend'))->pluck('role_id')->all();

        return view('admin.users.form', $this->formData($companyId) + compact('user', 'access', 'roleIds'));
    }

    public function update(AdminUserRequest $request, User $user, UserManagementService $service): RedirectResponse
    {
        $this->assertFrontendUser($user);
        $service->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('status', 'Frontend User updated successfully.');
    }

    public function activate(User $user, UserManagementService $service): RedirectResponse
    {
        $this->assertFrontendUser($user);
        $service->setStatus($user, 'Active');

        return back()->with('status', 'Frontend User activated.');
    }

    public function deactivate(User $user, UserManagementService $service): RedirectResponse
    {
        $this->assertFrontendUser($user);
        $service->setStatus($user, 'Inactive');

        return back()->with('status', 'Frontend User deactivated.');
    }

    public function resetPassword(Request $request, User $user, UserManagementService $service): RedirectResponse
    {
        $this->assertFrontendUser($user);
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        $service->resetPassword($user, $data['password']);

        return back()->with('status', 'Frontend User password reset successfully.');
    }

    public function departmentAccess(User $user, CurrentOrganizationContext $organization): View
    {
        $this->assertFrontendUser($user);
        $companyId = $organization->companyId();
        abort_unless($user->organizationAccess()->where('company_id', $companyId)->exists(), 404);
        $departments = Department::query()->where('company_id', $companyId)->where('status', 'Active')->with('factory')->orderBy('department_name')->get();
        $assigned = $user->departmentAccess()->where('company_id', $companyId)->where('status', 'Active')->pluck('department_id')->all();
        $home = $user->organizationAccess()->where('company_id', $companyId)->value('department_id');

        return view('admin.users.department-access', compact('user', 'departments', 'assigned', 'home'));
    }

    public function updateDepartmentAccess(DepartmentAccessRequest $request, User $user, DepartmentAccessService $service): RedirectResponse
    {
        $this->assertFrontendUser($user);
        $service->sync($user, $request->validated('department_ids', []));

        return redirect()->route('admin.users.department-access', $user)->with('status', 'Department Access updated successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(int $companyId): array
    {
        return [
            'roles' => Role::query()->where('company_id', $companyId)->where('scope', 'Company')->where('panel', 'Frontend')->where('status', 'Active')->orderBy('name')->get(),
            'individuals' => Individual::query()->where('status', '!=', 'Deleted')->whereIn('type', ['employee', 'master'])->orderBy('name')->get(['id', 'name', 'email']),
            'branches' => Branch::query()->where('company_id', $companyId)->where('status', 'Active')->orderBy('name')->get(),
            'factories' => Factory::query()->where('company_id', $companyId)->where('status', 'Active')->orderBy('name')->get(),
            'departments' => Department::query()->where('company_id', $companyId)->where('status', 'Active')->orderBy('name')->get(),
        ];
    }

    private function assertFrontendUser(User $user): void
    {
        abort_unless($user->user_type === 'User' && $user->status !== 'Deleted', 404);
    }
}
