<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserActivityLog;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class UserActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = UserActivityLog::query();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('user_id', 'like', '%'.$request->search.'%');
            });
        }

        $userActivityLogs = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.user_activity_logs.index', compact('userActivityLogs'));
    }

    public function destroy($id)
    {
        $id = dec($id);
        $userActivityLog = UserActivityLog::where('id', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            $userActivityLog->delete();

            DB::commit();
            Session::put('message', 'User Activity Logs deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete User Activity Logs. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

