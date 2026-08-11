<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Individual;
use App\Models\Shift;
use App\Rules\RecordStatusRule;
use App\Services\EmployeeMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Individual::query()->where('type', 'employee')->where('status', '!=', RecordStatus::Deleted->value)->with(['department', 'factory', 'shift', 'users'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$request->string('search').'%')->orWhere('employee_code', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('factory_id'), fn ($q) => $q->where('factory_id', $request->integer('factory_id')))
            ->when($request->filled('shift_id') && Schema::hasColumn('individuals', 'shift_id'), fn ($q) => $q->where('shift_id', $request->integer('shift_id')))
            ->when(in_array($request->status, ['Active', 'Inactive'], true), fn ($q) => $q->where('status', $request->status));

        return view('admin.employees.index', ['employees' => $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString(), ...$this->lookups()]);
    }

    public function create(): View
    {
        return view('admin.employees.create', $this->lookups());
    }

    public function store(Request $request, EmployeeMasterService $service): RedirectResponse
    {
        $service->save(new Individual(), $this->validated($request), $request);

        return redirect()->route('admin.employees.index')->with('message', 'Employee added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(Individual $employee): View
    {
        $this->assertEmployee($employee);

        return view('admin.employees.edit', ['employee' => $employee, ...$this->lookups($employee)]);
    }

    public function update(Request $request, Individual $employee, EmployeeMasterService $service): RedirectResponse
    {
        $this->assertEmployee($employee);
        $service->save($employee, $this->validated($request), $request);

        return redirect()->route('admin.employees.index')->with('message', 'Employee updated successfully.')->with('messageClass', 'successClass');
    }

    public function activate(Individual $employee, EmployeeMasterService $service): RedirectResponse
    {
        $this->assertEmployee($employee);
        $service->transition($employee, RecordStatus::Active->value);

        return back()->with('message', 'Employee activated successfully.');
    }

    public function deactivate(Individual $employee, EmployeeMasterService $service): RedirectResponse
    {
        $this->assertEmployee($employee);
        $service->transition($employee, RecordStatus::Inactive->value);

        return back()->with('message', 'Employee deactivated successfully.');
    }

    public function destroy(Request $request, Individual $employee, EmployeeMasterService $service): RedirectResponse
    {
        $this->assertEmployee($employee);
        $service->remove($employee, $request);

        return back()->with('message', 'Employee removed successfully.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:255', 'employee_code' => 'nullable|string|max:50', 'designation' => 'nullable|string|max:100', 'phone' => 'nullable|string|max:25', 'email' => 'nullable|email|max:100', 'department_id' => 'nullable|integer', 'factory_id' => 'nullable|integer', 'shift_id' => 'nullable|integer', 'status' => ['required', 'in:Active,Inactive', new RecordStatusRule()]]);
    }

    /** @return array<string, mixed> */
    private function lookups(?Individual $employee = null): array
    {
        $shifts = collect();
        if (Schema::hasTable('shifts')) {
            $shifts = Shift::query()->where(function ($q): void {
                $q->where('status', 'Active');
            })->when($employee?->shift_id, fn ($q) => $q->orWhereKey($employee->shift_id))->orderBy('start_time')->get();
        }

        return ['departments' => Department::active()->orderBy('department_name')->get(), 'factories' => Factory::active()->orderBy('name')->get(), 'shifts' => $shifts];
    }

    private function assertEmployee(Individual $employee): void
    {
        abort_if($employee->type !== 'employee' || $employee->status === RecordStatus::Deleted->value, 404);
    }
}
