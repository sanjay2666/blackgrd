<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficeIp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class OfficeIpController extends Controller
{
    public function index(Request $request)
    {
        $query = OfficeIp::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ip_address', 'like', '%'.$request->search.'%')
                    ->orWhere('label', 'like', '%'.$request->search.'%');
            });
        }

        $officeIps = $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.office_ips.index', compact('officeIps'));
    }

    public function create()
    {
        return view('admin.office_ips.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip|unique:office_ips,ip_address',
            'label' => 'nullable|max:255',
            'is_active' => 'required|in:1,0',
        ], [
            'ip_address.required' => 'Please enter IP Address.',
            'ip_address.ip' => 'Please enter valid IP Address.',
            'ip_address.unique' => 'IP Address already exists.',
            'label.max' => 'Label should not be greater than 255 characters.',
            'is_active.required' => 'Please select Status.',
            'is_active.in' => 'Please select valid Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $officeIp = new OfficeIp();
            $officeIp->ip_address = $request->ip_address;
            $officeIp->label = $request->label;
            $officeIp->is_active = $request->is_active;
            $officeIp->created_at = now();
            $officeIp->updated_at = now();
            $officeIp->save();

            DB::commit();
            Session::put('message', 'Office IP added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.office-ips.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save office IP. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit(OfficeIp $office_ip)
    {
        return view('admin.office_ips.edit', compact('office_ip'));
    }

    public function update(Request $request, OfficeIp $office_ip)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required|ip|unique:office_ips,ip_address,'.$office_ip->id,
            'label' => 'nullable|max:255',
            'is_active' => 'required|in:1,0',
        ], [
            'ip_address.required' => 'Please enter IP Address.',
            'ip_address.ip' => 'Please enter valid IP Address.',
            'ip_address.unique' => 'IP Address already exists.',
            'label.max' => 'Label should not be greater than 255 characters.',
            'is_active.required' => 'Please select Status.',
            'is_active.in' => 'Please select valid Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $office_ip->ip_address = $request->ip_address;
            $office_ip->label = $request->label;
            $office_ip->is_active = $request->is_active;
            $office_ip->updated_at = now();
            $office_ip->save();

            DB::commit();
            Session::put('message', 'Office IP updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.office-ips.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update office IP. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy(OfficeIp $office_ip)
    {
        DB::beginTransaction();
        try {
            $office_ip->delete();

            DB::commit();
            Session::put('message', 'Office IP deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete office IP. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
