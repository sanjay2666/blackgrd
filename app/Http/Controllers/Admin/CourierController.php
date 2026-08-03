<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CourierController extends Controller
{
    public function index(Request $request)
    {
        $query = Courier::query();
        $query->where('status', '!=', 'Deleted');

        $qsearch = trim($request->input('qsearch', ''));
        if ($qsearch == '' && $request->filled('search')) {
            $qsearch = trim($request->input('search', ''));
        }

        if ($qsearch != '') {
            $query->where(function ($query) use ($qsearch) {
                $query->where('cus_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('item_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('phone', 'like', '%'.$qsearch.'%');
                $query->orWhere('courier_name', 'like', '%'.$qsearch.'%');
                $query->orWhere('tracking_number', 'like', '%'.$qsearch.'%');
            });
        }

        $couriers = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.couriers.index', compact('couriers', 'qsearch'));
    }

    public function create()
    {
        return view('admin.couriers.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cus_id' => 'required',
            'cus_name' => 'required',
            'phone' => 'nullable',
            'whatsapp' => 'nullable',
            'item_id' => 'required',
            'item_name' => 'required',
            'tot_mtr' => 'required',
            'tot_pack' => 'required',
            'courier_name' => 'required',
            'tracking_number' => 'nullable',
            'track_url' => 'nullable',
            'status' => 'required|in:Active,Inactive',
        ], [
            'cus_id.required' => 'Please select Customer Name.',
            'cus_name.required' => 'Please enter Customer Name.',
            'item_id.required' => 'Please select Item Name.',
            'item_name.required' => 'Please enter Item Name.',
            'tot_mtr.required' => 'Please enter Packing details.',
            'tot_pack.required' => 'Please enter Total Pack.',
            'courier_name.required' => 'Please enter Courier Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $courier = new Courier();
            $courier->cus_id = $request->cus_id;
            $courier->cus_name = $request->cus_name;
            $courier->phone = $request->phone;
            $courier->whatsapp = $request->whatsapp;
            $courier->item_id = $request->item_id;
            $courier->item_name = $request->item_name;
            $courier->tot_mtr = $request->tot_mtr;
            $courier->tot_pack = $request->tot_pack;
            $courier->courier_name = $request->courier_name;
            $courier->tracking_number = $request->tracking_number;
            $courier->track_url = $request->track_url;
            $courier->is_msg_send = $request->input('is_msg_send', 'No');
            $courier->is_track_msg_send = $request->input('is_track_msg_send', 'No');
            $courier->status = $request->status;
            $courier->created = now();
            $courier->modified = now();
            $courier->created_by = Auth::guard('admin')->id();
            $courier->updated_by = Auth::guard('admin')->id();
            $courier->save();

            DB::commit();
            Session::put('message', 'Couriers added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.couriers.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Couriers. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $courier = Courier::where('id', $id)->firstOrFail();
        if ($courier->status === 'Deleted') {
            abort(404);
        }

        return view('admin.couriers.edit', compact('courier'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $courier = Courier::where('id', $id)->firstOrFail();
        if ($courier->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'cus_id' => 'required',
            'cus_name' => 'required',
            'phone' => 'nullable',
            'whatsapp' => 'nullable',
            'item_id' => 'required',
            'item_name' => 'required',
            'tot_mtr' => 'required',
            'tot_pack' => 'required',
            'courier_name' => 'required',
            'tracking_number' => 'nullable',
            'track_url' => 'nullable',
            'status' => 'required|in:Active,Inactive',
        ], [
            'cus_id.required' => 'Please select Customer Name.',
            'cus_name.required' => 'Please enter Customer Name.',
            'item_id.required' => 'Please select Item Name.',
            'item_name.required' => 'Please enter Item Name.',
            'tot_mtr.required' => 'Please enter Packing details.',
            'tot_pack.required' => 'Please enter Total Pack.',
            'courier_name.required' => 'Please enter Courier Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $courier->cus_id = $request->cus_id;
            $courier->cus_name = $request->cus_name;
            $courier->phone = $request->phone;
            $courier->whatsapp = $request->whatsapp;
            $courier->item_id = $request->item_id;
            $courier->item_name = $request->item_name;
            $courier->tot_mtr = $request->tot_mtr;
            $courier->tot_pack = $request->tot_pack;
            $courier->courier_name = $request->courier_name;
            $courier->tracking_number = $request->tracking_number;
            $courier->track_url = $request->track_url;
            $courier->is_msg_send = $request->input('is_msg_send', 'No');
            $courier->is_track_msg_send = $request->input('is_track_msg_send', 'No');
            $courier->status = $request->status;
            $courier->modified = now();
            $courier->updated_by = Auth::guard('admin')->id();
            $courier->save();

            DB::commit();
            Session::put('message', 'Couriers updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.couriers.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Couriers. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $courier = Courier::where('id', $id)->firstOrFail();
        if ($courier->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $courier->status = 'Deleted';
            $courier->is_deleted = 1;
            $courier->modified = now();
            $courier->updated_by = Auth::guard('admin')->id();
            $courier->deleted_by = Auth::guard('admin')->id();
            $courier->save();

            DB::commit();
            Session::put('message', 'Couriers deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Couriers. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

