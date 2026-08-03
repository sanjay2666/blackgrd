<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackagingType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PackagingTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = PackagingType::query();
        $query->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->search.'%');
            });
        }

        $packagingTypes = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.packaging_types.index', compact('packagingTypes'));
    }

    public function create()
    {
        return view('admin.packaging_types.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
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
            $packagingType = new PackagingType();
            $packagingType->name = $request->name;
            $packagingType->status = $request->status;
            $packagingType->created = now();
            $packagingType->modified = now();
            $packagingType->save();

            DB::commit();
            Session::put('message', 'Packaging Types added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.packaging-types.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Packaging Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $packagingType = PackagingType::where('id', $id)->firstOrFail();
        if ($packagingType->status === 'Deleted') {
            abort(404);
        }

        return view('admin.packaging_types.edit', compact('packagingType'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $packagingType = PackagingType::where('id', $id)->firstOrFail();
        if ($packagingType->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
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
            $packagingType->name = $request->name;
            $packagingType->status = $request->status;
            $packagingType->modified = now();
            $packagingType->save();

            DB::commit();
            Session::put('message', 'Packaging Types updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.packaging-types.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Packaging Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $packagingType = PackagingType::where('id', $id)->firstOrFail();
        if ($packagingType->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $packagingType->status = 'Deleted';
            $packagingType->modified = now();
            $packagingType->save();

            DB::commit();
            Session::put('message', 'Packaging Types deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Packaging Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

