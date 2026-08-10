<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserAuthController extends Controller
{
    public function showRegister()
    {
        return view('frontend.auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ], [
            'name.required' => 'Please enter Name.',
            'email.required' => 'Please enter Email.',
            'email.email' => 'Please enter valid Email.',
            'email.unique' => 'Email already exists.',
            'password.required' => 'Please enter Password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $user = User::create([
            'user_type' => 'User',
            'individual_id' => null,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'financial_year' => currentFinancialYear(),
        ]);

        $companyId = Company::query()->where('status', 'Active')->orderBy('id')->value('id');
        if ($companyId !== null) {
            UserOrganizationAccess::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'is_default' => true,
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function showLogin()
    {
        return view('frontend.auth.login');
    }

    public function showForgotPassword()
    {
        return view('frontend.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Please enter Email.',
            'email.email' => 'Please enter valid Email.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $user = User::where('email', $request->email)
            ->where('user_type', 'User')
            ->where('status', 'Active')
            ->first();

        if (empty($user)) {
            Session::put('message', 'This email address is not registered.');
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $resetLink = route('password.reset', ['token' => $token, 'email' => $request->email]);

        $body = '<p>Hello '.$user->name.',</p>';
        $body .= '<p>We received a request to reset your Loomexa password.</p>';
        $body .= '<p><a href="'.$resetLink.'" style="background:#0b8f84;color:#ffffff;padding:10px 16px;text-decoration:none;display:inline-block;">Reset Password</a></p>';
        $body .= '<p>If you did not request this, please ignore this email.</p>';

        $mailResult = sendMail($user->email, 'Reset Your Loomexa Password', mailBody($body), 'Loomexa');

        if (config('mail.default') == 'log') {
            Session::put('message', 'Mail is currently in log mode. Reset email is saved in storage/logs/laravel.log. Please configure SMTP to receive email in inbox.');
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        if ($mailResult != 'Message sent!') {
            Session::put('message', $mailResult);
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        Session::put('message', 'Password reset link has been sent to your email.');
        Session::put('messageClass', 'successClass');

        return redirect()->route('login');
    }

    public function showResetPassword(Request $request, $token)
    {
        return view('frontend.auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:8',
        ], [
            'email.required' => 'Please enter Email.',
            'email.email' => 'Please enter valid Email.',
            'token.required' => 'Reset token is missing.',
            'password.required' => 'Please enter Password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $resetData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (empty($resetData) || ! Hash::check($request->token, $resetData->token)) {
            Session::put('message', 'Password reset link is invalid.');
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $tokenTime = strtotime($resetData->created_at);
        if ($tokenTime < strtotime('-60 minutes')) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            Session::put('message', 'Password reset link has expired.');
            Session::put('messageClass', 'errorClass');

            return redirect()->route('password.request');
        }

        $user = User::where('email', $request->email)
            ->where('user_type', 'User')
            ->where('status', 'Active')
            ->first();

        if (empty($user)) {
            Session::put('message', 'User account not found.');
            Session::put('messageClass', 'errorClass');

            return redirect()->route('password.request');
        }

        $user->password = Hash::make($request->password);
        $user->updated_at = date('Y-m-d H:i:s');
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        Session::put('message', 'Password reset successfully. Please login with your new password.');
        Session::put('messageClass', 'successClass');

        return redirect()->route('login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Please enter Email.',
            'email.email' => 'Please enter valid Email.',
            'password.required' => 'Please enter Password.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'user_type' => 'User',
            'status' => 'Active',
        ];

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        Session::put('message', 'Email or Password is incorrect.');
        Session::put('messageClass', 'errorClass');

        return redirect()->back()->withInput();
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Logged out successfully.');
    }
}
