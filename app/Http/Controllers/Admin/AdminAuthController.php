<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            if (! $user->isAdmin()) {
                Auth::logout();
                return back()->withErrors(['email' => $user->role === 'admin'
                    ? 'This admin account has been turned off. Ask a super admin to turn it back on.'
                    : 'Only admin users may sign in here.']);
            }

            return redirect()->route($user->must_change_password ? 'admin.password.edit' : ($user->adminHomeRoute() ?? 'admin.password.edit'));
        }

        return back()->withErrors(['email' => 'Invalid admin credentials provided.'])->withInput();
    }
}
