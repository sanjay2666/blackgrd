<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\State;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $query = State::query();
        $query->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            });
        }

        $states = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.states.index', compact('states'));
    }

    public function create()
    {
        return view('admin.states.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'country_id' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'name.required' => 'Please enter State Name.',
            'country_id.required' => 'Please enter Country Id.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $state = new State();
            $state->name = $request->name;
            $state->country_id = $request->country_id;
            $state->status = $request->status;
            $state->save();

            DB::commit();
            Session::put('message', 'States added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.states.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save States. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $state = State::where('id', $id)->firstOrFail();
        if ($state->status === 'Deleted') {
            abort(404);
        }

        return view('admin.states.edit', compact('state'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $state = State::where('id', $id)->firstOrFail();
        if ($state->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'country_id' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'name.required' => 'Please enter State Name.',
            'country_id.required' => 'Please enter Country Id.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $state->name = $request->name;
            $state->country_id = $request->country_id;
            $state->status = $request->status;
            $state->save();

            DB::commit();
            Session::put('message', 'States updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.states.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update States. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $state = State::where('id', $id)->firstOrFail();
        if ($state->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $state->status = 'Deleted';
            $state->save();

            DB::commit();
            Session::put('message', 'States deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete States. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

