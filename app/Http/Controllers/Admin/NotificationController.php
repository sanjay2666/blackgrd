<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();
        $query->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('model_name', 'like', '%'.$request->search.'%');
                $query->orWhere('title', 'like', '%'.$request->search.'%');
                $query->orWhere('message', 'like', '%'.$request->search.'%');
                $query->orWhere('page_name', 'like', '%'.$request->search.'%');
            });
        }

        $notifications = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_type_id' => 'nullable',
            'user_id' => 'required',
            'emp_id' => 'nullable',
            'model_name' => 'required',
            'ref_id' => 'nullable',
            'ref_table' => 'nullable',
            'notification_type' => 'nullable',
            'title' => 'nullable',
            'page_link' => 'required',
            'message' => 'required',
            'page_name' => 'required',
            'ip_address' => 'required',
            'server_details' => 'required',
            'is_read' => 'nullable',
            'status' => 'required|in:Active,Inactive',
        ], [
            'user_id.required' => 'Please enter User Id.',
            'model_name.required' => 'Please enter Model Name.',
            'page_link.required' => 'Please enter Page Link.',
            'message.required' => 'Please enter Message.',
            'page_name.required' => 'Please enter Page Name.',
            'ip_address.required' => 'Please enter IP Address.',
            'server_details.required' => 'Please enter Server Details.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $notification = new Notification();
            $notification->process_type_id = $request->process_type_id;
            $notification->user_id = $request->user_id;
            $notification->emp_id = $request->emp_id;
            $notification->model_name = $request->model_name;
            $notification->ref_id = $request->ref_id;
            $notification->ref_table = $request->ref_table;
            $notification->notification_type = $request->notification_type;
            $notification->title = $request->title;
            $notification->page_link = $request->page_link;
            $notification->message = $request->message;
            $notification->page_name = $request->page_name;
            $notification->ip_address = $request->ip_address;
            $notification->server_details = $request->server_details;
            $notification->is_read = $request->has('is_read') ? 1 : 0;
            $notification->status = $request->status;
            $notification->created = now();
            $notification->modified = now();
            $notification->save();

            DB::commit();
            Session::put('message', 'Notifications added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.notifications.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Notifications. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $notification = Notification::where('id', $id)->firstOrFail();
        if ($notification->status === 'Deleted') {
            abort(404);
        }

        return view('admin.notifications.edit', compact('notification'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $notification = Notification::where('id', $id)->firstOrFail();
        if ($notification->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'process_type_id' => 'nullable',
            'user_id' => 'required',
            'emp_id' => 'nullable',
            'model_name' => 'required',
            'ref_id' => 'nullable',
            'ref_table' => 'nullable',
            'notification_type' => 'nullable',
            'title' => 'nullable',
            'page_link' => 'required',
            'message' => 'required',
            'page_name' => 'required',
            'ip_address' => 'required',
            'server_details' => 'required',
            'is_read' => 'nullable',
            'status' => 'required|in:Active,Inactive',
        ], [
            'user_id.required' => 'Please enter User Id.',
            'model_name.required' => 'Please enter Model Name.',
            'page_link.required' => 'Please enter Page Link.',
            'message.required' => 'Please enter Message.',
            'page_name.required' => 'Please enter Page Name.',
            'ip_address.required' => 'Please enter IP Address.',
            'server_details.required' => 'Please enter Server Details.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $notification->process_type_id = $request->process_type_id;
            $notification->user_id = $request->user_id;
            $notification->emp_id = $request->emp_id;
            $notification->model_name = $request->model_name;
            $notification->ref_id = $request->ref_id;
            $notification->ref_table = $request->ref_table;
            $notification->notification_type = $request->notification_type;
            $notification->title = $request->title;
            $notification->page_link = $request->page_link;
            $notification->message = $request->message;
            $notification->page_name = $request->page_name;
            $notification->ip_address = $request->ip_address;
            $notification->server_details = $request->server_details;
            $notification->is_read = $request->has('is_read') ? 1 : 0;
            $notification->status = $request->status;
            $notification->modified = now();
            $notification->save();

            DB::commit();
            Session::put('message', 'Notifications updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.notifications.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Notifications. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $notification = Notification::where('id', $id)->firstOrFail();
        if ($notification->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $notification->status = 'Deleted';
            $notification->modified = now();
            $notification->save();

            DB::commit();
            Session::put('message', 'Notifications deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Notifications. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

