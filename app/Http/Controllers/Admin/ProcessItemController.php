<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\ProcessItem;
use App\Rules\RecordStatusRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ProcessItemController extends Controller
{
    public function index(Request $request)
    {
        $query = ProcessItem::notDeleted();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('entry_name', 'like', '%'.$request->search.'%')
                    ->orWhere('process_name', 'like', '%'.$request->search.'%')
                    ->orWhere('output_name', 'like', '%'.$request->search.'%');
            });
        }

        return view('admin.process_items.index', [
            'processItems' => $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.process_items.create', ['statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entry_name' => 'nullable|max:255',
            'process_name' => 'required|max:255',
            'output_name' => 'required|max:255',
            'process_sl_no_last' => 'required|integer|min:0',
            'status' => ['required', new RecordStatusRule],
        ], [
            'entry_name.max' => 'Entry Name should not be greater than 255 characters.',
            'process_name.required' => 'Please enter Process Name.',
            'process_name.max' => 'Process Name should not be greater than 255 characters.',
            'output_name.required' => 'Please enter Output Name.',
            'output_name.max' => 'Output Name should not be greater than 255 characters.',
            'process_sl_no_last.required' => 'Please enter Last Sl No.',
            'process_sl_no_last.integer' => 'Last Sl No should be a number.',
            'process_sl_no_last.min' => 'Last Sl No should not be less than 0.',
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
            $processItem = new ProcessItem;
            $processItem->entry_name = $request->entry_name;
            $processItem->process_name = $request->process_name;
            $processItem->output_name = $request->output_name;
            $processItem->process_sl_no_last = $request->process_sl_no_last;
            $processItem->created = now();
            $processItem->status = $request->status;
            $processItem->save();

            DB::commit();
            Session::put('message', 'Process item added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.process-items.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save process item. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit(ProcessItem $process_item)
    {
        abort_if($process_item->status === 'Deleted', 404);

        return view('admin.process_items.edit', [
            'processItem' => $process_item,
            'statusOptions' => RecordStatus::formOptions(),
        ]);
    }

    public function update(Request $request, ProcessItem $process_item)
    {
        abort_if($process_item->status === 'Deleted', 404);

        $validator = Validator::make($request->all(), [
            'entry_name' => 'nullable|max:255',
            'process_name' => 'required|max:255',
            'output_name' => 'required|max:255',
            'process_sl_no_last' => 'required|integer|min:0',
            'status' => ['required', new RecordStatusRule],
        ], [
            'entry_name.max' => 'Entry Name should not be greater than 255 characters.',
            'process_name.required' => 'Please enter Process Name.',
            'process_name.max' => 'Process Name should not be greater than 255 characters.',
            'output_name.required' => 'Please enter Output Name.',
            'output_name.max' => 'Output Name should not be greater than 255 characters.',
            'process_sl_no_last.required' => 'Please enter Last Sl No.',
            'process_sl_no_last.integer' => 'Last Sl No should be a number.',
            'process_sl_no_last.min' => 'Last Sl No should not be less than 0.',
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
            $process_item->entry_name = $request->entry_name;
            $process_item->process_name = $request->process_name;
            $process_item->output_name = $request->output_name;
            $process_item->process_sl_no_last = $request->process_sl_no_last;
            $process_item->modified = now();
            $process_item->status = $request->status;
            $process_item->save();

            DB::commit();
            Session::put('message', 'Process item updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.process-items.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update process item. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy(ProcessItem $process_item)
    {
        abort_if($process_item->status === 'Deleted', 404);

        DB::beginTransaction();
        try {
            $process_item->status = 'Deleted';
            $process_item->modified = now();
            $process_item->save();

            DB::commit();
            Session::put('message', 'Process item deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete process item. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
