<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GstRate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class GstRateController extends Controller
{
    public function index(Request $request)
    {
        $query = GstRate::query();
        $query->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('gst_rate', 'like', '%'.$request->search.'%');
            });
        }

        $gstRates = $query->orderBy('gst_rate_id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.gst_rates.index', compact('gstRates'));
    }

    public function create()
    {
        return view('admin.gst_rates.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gst_rate' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'gst_rate.required' => 'Please enter GST Rate.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $gstRate = new GstRate();
            $gstRate->gst_rate = $request->gst_rate;
            $gstRate->status = $request->status;
            $gstRate->created = now();
            $gstRate->modified = now();
            $gstRate->save();

            DB::commit();
            Session::put('message', 'GST Rates added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.gst-rates.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save GST Rates. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $gstRate = GstRate::where('gst_rate_id', $id)->firstOrFail();
        if ($gstRate->status === 'Deleted') {
            abort(404);
        }

        return view('admin.gst_rates.edit', compact('gstRate'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $gstRate = GstRate::where('gst_rate_id', $id)->firstOrFail();
        if ($gstRate->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'gst_rate' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'gst_rate.required' => 'Please enter GST Rate.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $gstRate->gst_rate = $request->gst_rate;
            $gstRate->status = $request->status;
            $gstRate->modified = now();
            $gstRate->save();

            DB::commit();
            Session::put('message', 'GST Rates updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.gst-rates.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update GST Rates. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $gstRate = GstRate::where('gst_rate_id', $id)->firstOrFail();
        if ($gstRate->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $gstRate->status = 'Deleted';
            $gstRate->modified = now();
            $gstRate->save();

            DB::commit();
            Session::put('message', 'GST Rates deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete GST Rates. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

