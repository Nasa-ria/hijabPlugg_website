<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * "My password" for every admin. New admins are sent here until they replace their temporary password.
 */
class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.password.edit', ['mustChange' => $request->user()->must_change_password]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'That isn\'t your current password.',
            'password.different' => 'Choose a new password that\'s different from the current one.',
        ]);

        $user = $request->user();
        $user->update(['password' => $request->password, 'must_change_password' => false]);
        $request->session()->regenerate();

        $home = $user->adminHomeRoute();

        return $home
            ? redirect()->route($home)->with('success', 'Your password has been updated.')
            : redirect()->route('admin.password.edit')->with('success', 'Your password has been updated. Ask a super admin to give you access to some pages.');
    }
}
