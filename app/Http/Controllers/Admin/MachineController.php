<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\ProcessItem;
use App\Rules\RecordStatusRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class MachineController extends Controller
{
    public function index(Request $request)
    {
        $query = Machine::with('processItem')->notDeleted();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhereHas('processItem', function ($processQuery) use ($request) {
                        $processQuery->where('process_name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $machines = $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.machines.index', compact('machines'));
    }

    public function create()
    {
        $processItems = ProcessItem::active()->orderBy('id', 'asc')->get();

        return view('admin.machines.create', compact('processItems'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'process_wise' => 'required|integer|exists:process_items,id',
            'is_busy' => 'nullable|in:1,0',
            'status' => ['required', new RecordStatusRule],
        ], [
            'name.required' => 'Please enter Machine Name.',
            'name.max' => 'Machine Name should not be greater than 255 characters.',
            'process_wise.required' => 'Please select Process.',
            'process_wise.exists' => 'Please select valid Process.',
            'is_busy.in' => 'Please select valid Busy Status.',
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
            $machine = new Machine;
            $machine->name = $request->name;
            $machine->process_wise = $request->process_wise;
            $machine->is_busy = $request->is_busy ?? '0';
            $machine->created_by = Auth::guard('admin')->id();
            $machine->created = now();
            $machine->created_at = now();
            $machine->status = $request->status;
            $machine->save();

            DB::commit();
            Session::put('message', 'Machine added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.machines.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save machine. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit(Machine $machine)
    {
        abort_if($machine->status === 'Deleted', 404);

        $processItems = ProcessItem::active()->orderBy('id', 'asc')->get();

        return view('admin.machines.edit', compact('machine', 'processItems'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, Machine $machine)
    {
        abort_if($machine->status === 'Deleted', 404);

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'process_wise' => 'required|integer|exists:process_items,id',
            'is_busy' => 'nullable|in:1,0',
            'status' => ['required', new RecordStatusRule],
        ], [
            'name.required' => 'Please enter Machine Name.',
            'name.max' => 'Machine Name should not be greater than 255 characters.',
            'process_wise.required' => 'Please select Process.',
            'process_wise.exists' => 'Please select valid Process.',
            'is_busy.in' => 'Please select valid Busy Status.',
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
            $machine->name = $request->name;
            $machine->process_wise = $request->process_wise;
            $machine->is_busy = $request->is_busy ?? '0';
            $machine->modified_by = Auth::guard('admin')->id();
            $machine->modified = now();
            $machine->updated_at = now();
            $machine->status = $request->status;
            $machine->save();

            DB::commit();
            Session::put('message', 'Machine updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.machines.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update machine. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy(Machine $machine)
    {
        abort_if($machine->status === 'Deleted', 404);

        DB::beginTransaction();
        try {
            $machine->status = 'Deleted';
            $machine->modified_by = Auth::guard('admin')->id();
            $machine->modified = now();
            $machine->updated_at = now();
            $machine->save();

            DB::commit();
            Session::put('message', 'Machine deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete machine. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
