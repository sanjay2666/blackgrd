<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Colour;
use App\Rules\RecordStatusRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ColourController extends Controller
{
    public function index(Request $request)
    {
        $query = Colour::query();
        $query->notDeleted();

        $qsearch = trim($request->input('qsearch', ''));
        if ($qsearch == '' && $request->filled('search')) {
            $qsearch = trim($request->input('search', ''));
        }

        if ($qsearch != '') {
            $query->where(function ($query) use ($qsearch) {
                $query->where('name', 'like', '%'.$qsearch.'%');
                $query->orWhere('code', 'like', '%'.$qsearch.'%');
            });
        }

        $colours = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.colours.index', compact('colours', 'qsearch'));
    }

    public function create()
    {
        return view('admin.colours.create', ['statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'code' => 'nullable',
            'status' => ['required', new RecordStatusRule],
        ], [
            'name.required' => 'Please enter Colour Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $colour = new Colour;
            $colour->name = $request->name;
            $colour->code = $request->code;
            $colour->status = $request->status;
            $colour->created = now();
            $colour->modified = now();
            $colour->save();

            DB::commit();
            Session::put('message', 'Colours added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.colours.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Colours. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $colour = Colour::where('id', $id)->firstOrFail();
        if ($colour->status === 'Deleted') {
            abort(404);
        }

        return view('admin.colours.edit', compact('colour'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $colour = Colour::where('id', $id)->firstOrFail();
        if ($colour->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'code' => 'nullable',
            'status' => ['required', new RecordStatusRule],
        ], [
            'name.required' => 'Please enter Colour Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $colour->name = $request->name;
            $colour->code = $request->code;
            $colour->status = $request->status;
            $colour->modified = now();
            $colour->save();

            DB::commit();
            Session::put('message', 'Colours updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.colours.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Colours. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $colour = Colour::where('id', $id)->firstOrFail();
        if ($colour->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $colour->status = 'Deleted';
            $colour->modified = now();
            $colour->deleted_by = auth('admin')->id();
            $colour->deleted_date = now();
            $colour->save();

            DB::commit();
            Session::put('message', 'Colours deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Colours. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
