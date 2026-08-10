<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Rules\RecordStatusRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::notDeleted();

        if ($request->filled('search')) {
            $query->where('department_name', 'like', '%'.$request->search.'%');
        }

        return view('admin.departments.index', [
            'departments' => $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.departments.create', ['statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|max:255',
            'status' => ['required', new RecordStatusRule],
        ], [
            'department_name.required' => 'Please enter Department Name.',
            'department_name.max' => 'Department Name should not be greater than 255 characters.',
            'status.required' => 'Please select Status.',
            'status.in' => 'Please select valid Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $department = new Department;
            $department->department_name = $request->department_name;
            $department->financial_year = currentFinancialYear();
            $department->created_by = Auth::guard('admin')->id();
            $department->created_at = now();
            $department->status = $request->status;
            $department->save();

            DB::commit();
            Session::put('message', 'Department added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.departments.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save department. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit(Department $department)
    {
        abort_if($department->status === 'Deleted', 404);

        return view('admin.departments.edit', [
            'department' => $department,
            'statusOptions' => RecordStatus::formOptions(),
        ]);
    }

    public function update(Request $request, Department $department)
    {
        abort_if($department->status === 'Deleted', 404);

        $validator = Validator::make($request->all(), [
            'department_name' => 'required|max:255',
            'status' => ['required', new RecordStatusRule],
        ], [
            'department_name.required' => 'Please enter Department Name.',
            'department_name.max' => 'Department Name should not be greater than 255 characters.',
            'status.required' => 'Please select Status.',
            'status.in' => 'Please select valid Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $department->department_name = $request->department_name;
            $department->financial_year = currentFinancialYear();
            $department->modified_by = Auth::guard('admin')->id();
            $department->updated_at = now();
            $department->status = $request->status;
            $department->save();

            DB::commit();
            Session::put('message', 'Department updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.departments.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update department. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy(Department $department)
    {
        abort_if($department->status === 'Deleted', 404);

        DB::beginTransaction();
        try {
            $department->status = 'Deleted';
            $department->modified_by = Auth::guard('admin')->id();
            $department->updated_at = now();
            $department->save();

            DB::commit();
            Session::put('message', 'Department deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete department. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
