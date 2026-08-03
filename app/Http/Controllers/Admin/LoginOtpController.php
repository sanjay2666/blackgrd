<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginOtp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LoginOtpController extends Controller
{
    public function index(Request $request)
    {
        $query = LoginOtp::query();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('user_id', 'like', '%'.$request->search.'%');
            });
        }

        $loginOtps = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.login_otps.index', compact('loginOtps'));
    }

    public function destroy($id)
    {
        $id = dec($id);
        $loginOtp = LoginOtp::where('id', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            $loginOtp->delete();

            DB::commit();
            Session::put('message', 'Login OTPs deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Login OTPs. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

