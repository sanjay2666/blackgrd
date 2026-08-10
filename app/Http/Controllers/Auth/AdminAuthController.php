<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\SafeAuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['user_type'] = 'Admin';
        $credentials['status'] = 'Active';

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $admin = Auth::guard('admin')->user();
            if ($admin !== null && Hash::needsRehash($admin->getAuthPassword())) {
                $admin->forceFill(['password' => Hash::make($request->password)])->saveQuietly();
            }

            $request->session()->forget(['organization.company_id', 'organization.factory_id']);
            $request->session()->regenerate();

            return app(SafeAuthRedirect::class)->intended($request, route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email or Password is incorrect.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->forget(['organization.company_id', 'organization.factory_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Admin logged out successfully.');
    }
}
