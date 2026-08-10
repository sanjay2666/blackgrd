<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Factory;
use App\Rules\RecordStatusRule;
use App\Services\CurrentOrganizationContext;
use App\Services\DepartmentMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Department::notDeleted();

        if ($request->filled('search')) {
            $query->where('department_name', 'like', '%'.$request->string('search').'%');
        }
        $query->when($request->filled('factory_id'), fn ($q) => $q->where('factory_id', $request->integer('factory_id')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return view('admin.departments.index', [
            'departments' => $query->with('factory')->latest('id')->paginate(config('app.pagination_limit'))->withQueryString(),
            'factories' => Factory::active()->orderBy('name')->get(),
            'statusOptions' => RecordStatus::formOptions(),
        ]);
    }

    public function create(CurrentOrganizationContext $organization): View
    {
        return view('admin.departments.create', $this->formData($organization));
    }

    public function store(Request $request, DepartmentMasterService $service): RedirectResponse
    {
        $data = $this->validated($request);
        $service->save(new Department, $data);

        return redirect()->route('admin.departments.index')->with('message', 'Department added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(Department $department, CurrentOrganizationContext $organization): View
    {
        abort_if($department->status === 'Deleted', 404);

        return view('admin.departments.edit', array_merge(['department' => $department], $this->formData($organization)));
    }

    public function update(Request $request, Department $department, DepartmentMasterService $service): RedirectResponse
    {
        abort_if($department->status === 'Deleted', 404);
        $service->save($department, $this->validated($request));

        return redirect()->route('admin.departments.index')->with('message', 'Department updated successfully.')->with('messageClass', 'successClass');
    }

    public function activate(Department $department, DepartmentMasterService $service): RedirectResponse
    {
        abort_if($department->status === 'Deleted', 404);
        $service->transition($department, 'Active');

        return back()->with('message', 'Department activated successfully.')->with('messageClass', 'successClass');
    }

    public function deactivate(Department $department, DepartmentMasterService $service): RedirectResponse
    {
        abort_if($department->status === 'Deleted', 404);
        $service->transition($department, 'Inactive');

        return back()->with('message', 'Department deactivated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy(Department $department): RedirectResponse
    {
        abort(422, 'Departments cannot be deleted; deactivate the department instead.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'department_name' => 'required|string|max:255',
            'factory_id' => 'nullable|integer',
            'financial_year' => 'nullable|string|max:4',
            'status' => ['required', new RecordStatusRule],
        ]);
    }

    /** @return array<string, mixed> */
    private function formData(CurrentOrganizationContext $organization): array
    {
        return ['factories' => Factory::active()->where('company_id', $organization->companyId())->orderBy('name')->get(), 'statusOptions' => RecordStatus::formOptions()];
    }
}
