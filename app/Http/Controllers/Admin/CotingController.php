<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CotingController extends Controller
{
    public function index(Request $request)
    {
        $query = Coting::query();
        $query->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
                $query->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        $cotings = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.cotings.index', compact('cotings'));
    }

    public function create()
    {
        return view('admin.cotings.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'name' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'code.required' => 'Please enter Code.',
            'name.required' => 'Please enter Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $coting = new Coting();
            $coting->code = $request->code;
            $coting->name = $request->name;
            $coting->status = $request->status;
            $coting->created = now();
            $coting->modified = now();
            $coting->save();

            DB::commit();
            Session::put('message', 'Cotings added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.cotings.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Cotings. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $coting = Coting::where('id', $id)->firstOrFail();
        if ($coting->status === 'Deleted') {
            abort(404);
        }

        return view('admin.cotings.edit', compact('coting'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $coting = Coting::where('id', $id)->firstOrFail();
        if ($coting->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'name' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'code.required' => 'Please enter Code.',
            'name.required' => 'Please enter Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $coting->code = $request->code;
            $coting->name = $request->name;
            $coting->status = $request->status;
            $coting->modified = now();
            $coting->save();

            DB::commit();
            Session::put('message', 'Cotings updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.cotings.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Cotings. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $coting = Coting::where('id', $id)->firstOrFail();
        if ($coting->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $coting->status = 'Deleted';
            $coting->modified = now();
            $coting->save();

            DB::commit();
            Session::put('message', 'Cotings deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Cotings. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

