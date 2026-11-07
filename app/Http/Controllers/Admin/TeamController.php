<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminInviteMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Super admins add admins, choose which pages each can use, and send sign-in details.
 * (Route access is limited to super admins by the AdminAccess middleware: the "team" area.)
 */
class TeamController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'admin')->orderByDesc('is_super_admin')->orderBy('name')->get();

        return view('admin.team.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.team.form', ['member' => new User(['admin_permissions' => ['orders']])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $temporaryPassword = Str::password(12, symbols: false);
        $member = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $temporaryPassword,
            'role' => 'admin',
            'is_active' => true,
            'is_super_admin' => $data['is_super_admin'],
            'admin_permissions' => $data['admin_permissions'],
            'must_change_password' => true,
            'invited_by' => $request->user()->id,
        ]);

        return $this->sendInvite($member, $temporaryPassword, $request->user(), 'added');
    }

    public function edit(User $member)
    {
        abort_unless($member->role === 'admin', 404);

        return view('admin.team.form', compact('member'));
    }

    public function update(Request $request, User $member)
    {
        abort_unless($member->role === 'admin', 404);
        $data = $this->validated($request, $member);
        $isSelf = $member->is($request->user());

        // Never lock yourself out or leave the store without a super admin
        if ($isSelf && (! $data['is_super_admin'] || ! $data['is_active'])) {
            return back()->withInput()->with('error', 'You can\'t remove your own super admin access or turn yourself off.');
        }
        if ($member->is_super_admin && ! $data['is_super_admin'] && $this->superAdminCount() <= 1) {
            return back()->withInput()->with('error', 'The store needs at least one super admin.');
        }

        $member->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'is_super_admin' => $data['is_super_admin'],
            'admin_permissions' => $data['admin_permissions'],
            'is_active' => $data['is_active'],
        ]);

        return redirect()->route('admin.team.index')->with('success', $member->name . '\'s access has been updated.');
    }

    /**
     * Remove admin access (the person keeps a normal customer account and their order history).
     */
    public function destroy(Request $request, User $member)
    {
        abort_unless($member->role === 'admin', 404);

        if ($member->is($request->user())) {
            return back()->with('error', 'You can\'t remove your own admin access.');
        }
        if ($member->is_super_admin && $this->superAdminCount() <= 1) {
            return back()->with('error', 'The store needs at least one super admin.');
        }

        $member->update(['role' => 'customer', 'is_super_admin' => false, 'admin_permissions' => null, 'must_change_password' => false]);

        return redirect()->route('admin.team.index')->with('success', $member->name . ' is no longer an admin.');
    }

    /**
     * New temporary password + email, e.g. if they lost the first email or forgot their password.
     */
    public function resendInvite(Request $request, User $member)
    {
        abort_unless($member->role === 'admin', 404);
        if ($member->is($request->user())) {
            return back()->with('error', 'Use "My password" to change your own password.');
        }

        $temporaryPassword = Str::password(12, symbols: false);
        $member->update(['password' => $temporaryPassword, 'must_change_password' => true]);

        return $this->sendInvite($member, $temporaryPassword, $request->user(), 'sent new sign-in details to');
    }

    private function sendInvite(User $member, string $temporaryPassword, User $by, string $verb)
    {
        try {
            Mail::to($member->email)->send(new AdminInviteMail($member, $temporaryPassword, $by));

            return redirect()->route('admin.team.index')
                ->with('success', ucfirst($verb === 'added' ? 'added ' . $member->name . ' and emailed their sign-in details to ' . $member->email : $verb . ' ' . $member->email) . '.');
        } catch (\Throwable $e) {
            Log::error('Admin invite email failed', ['admin' => $member->email, 'error' => $e->getMessage()]);

            // Show the temporary password once so the super admin can share it another way
            return redirect()->route('admin.team.index')
                ->with('error', 'The email to ' . $member->email . ' could not be sent (' . Str::limit($e->getMessage(), 120) . ').')
                ->with('invite_fallback', ['email' => $member->email, 'password' => $temporaryPassword]);
        }
    }

    private function validated(Request $request, ?User $member = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($member?->id)],
            'is_super_admin' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'admin_permissions' => 'nullable|array',
            'admin_permissions.*' => ['string', Rule::in(array_keys(User::ADMIN_SECTIONS))],
        ], [
            'email.unique' => 'Someone already has an account with this email.',
        ]);

        $data['is_super_admin'] = $request->boolean('is_super_admin');
        $data['is_active'] = $member ? $request->boolean('is_active') : true;
        $data['admin_permissions'] = $data['is_super_admin'] ? null : array_values($data['admin_permissions'] ?? []);

        if (! $data['is_super_admin'] && empty($data['admin_permissions'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'admin_permissions' => 'Choose at least one page this admin can use.',
            ]);
        }

        return $data;
    }

    private function superAdminCount(): int
    {
        return User::where('role', 'admin')->where('is_super_admin', true)->where('is_active', true)->count();
    }
}
