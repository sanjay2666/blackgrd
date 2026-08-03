<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LoginAttemptController extends Controller
{
    public function index(Request $request)
    {
        $query = LoginAttempt::query();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('email', 'like', '%'.$request->search.'%');
                $query->orWhere('ip', 'like', '%'.$request->search.'%');
                $query->orWhere('city', 'like', '%'.$request->search.'%');
                $query->orWhere('status', 'like', '%'.$request->search.'%');
            });
        }

        $loginAttempts = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.login_attempts.index', compact('loginAttempts'));
    }

    public function destroy($id)
    {
        $id = dec($id);
        $loginAttempt = LoginAttempt::where('id', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            $loginAttempt->delete();

            DB::commit();
            Session::put('message', 'Login Attempts deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Login Attempts. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

