@extends('layouts.admin')

@section('page_title', $mustChange ? 'Choose your password' : 'My password')

@section('content')
<div class="max-w-xl space-y-6">
    @if($mustChange)
        <div class="admin-card border-accent-300 bg-accent-50">
            <p class="font-semibold text-brand-900"><i class="fas fa-shield-halved mr-2 text-accent-600" aria-hidden="true"></i>Welcome! One quick step first</p>
            <p class="mt-1 text-sm text-gray-600">You signed in with a temporary password. Choose your own password to continue to the admin.</p>
        </div>
    @endif

    <form action="{{ route('admin.password.update') }}" method="POST" class="admin-card space-y-5">
        @csrf
        @method('PUT')
        <div>
            <label for="current-password" class="admin-label">{{ $mustChange ? 'Temporary password (from your email)' : 'Current password' }}</label>
            <input id="current-password" type="password" name="current_password" required autocomplete="current-password" class="input-field">
            @error('current_password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="new-password" class="admin-label">New password</label>
            <input id="new-password" type="password" name="password" required autocomplete="new-password" minlength="8" class="input-field" aria-describedby="password-help">
            <p id="password-help" class="mt-1 text-xs text-gray-500">At least 8 characters, with letters and numbers.</p>
            @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="confirm-password" class="admin-label">Confirm new password</label>
            <input id="confirm-password" type="password" name="password_confirmation" required autocomplete="new-password" class="input-field">
        </div>
        <button type="submit" class="btn-brand">{{ $mustChange ? 'Save and continue' : 'Update password' }}</button>
    </form>
</div>
@endsection
