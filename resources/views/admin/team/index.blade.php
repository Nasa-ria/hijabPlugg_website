@extends('layouts.admin')

@section('page_title', 'Team')

@section('content')
<div class="space-y-6">
    {{-- Shown once when an invitation email could not be sent --}}
    @if($fallback = session('invite_fallback'))
        <div class="admin-card border-accent-300 bg-accent-50" role="alert">
            <p class="font-semibold text-brand-900"><i class="fas fa-key mr-2 text-accent-600" aria-hidden="true"></i>Share these sign-in details another way</p>
            <p class="mt-2 text-sm text-gray-700">
                Email: <strong>{{ $fallback['email'] }}</strong> · Temporary password:
                <code class="rounded bg-white px-2 py-0.5 font-mono text-base text-brand-900">{{ $fallback['password'] }}</code>
            </p>
            <p class="mt-2 text-xs text-gray-500">This is shown only once. They'll be asked to choose their own password when they sign in.</p>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500">Admins and the pages they can use. Only super admins can see this page.</p>
        <a href="{{ route('admin.team.create') }}" class="btn-brand"><i class="fas fa-user-plus" aria-hidden="true"></i> Add admin</a>
    </div>

    <ul class="space-y-3">
        @foreach($admins as $member)
            <li class="admin-card flex flex-col gap-4 lg:flex-row lg:items-center">
                <div class="flex items-center gap-3 lg:w-72 lg:flex-shrink-0">
                    <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full {{ $member->is_active ? 'bg-accent-500' : 'bg-gray-300' }} font-bold text-white">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-brand-900">{{ $member->name }} @if($member->is(auth()->user()))<span class="text-xs font-normal text-gray-400">(you)</span>@endif</p>
                        <p class="truncate text-sm text-gray-500">{{ $member->email }}</p>
                    </div>
                </div>

                <div class="flex flex-1 flex-wrap items-center gap-1.5">
                    @if($member->is_super_admin)
                        <span class="rounded-full bg-brand-900 px-3 py-1 text-xs font-semibold text-white"><i class="fas fa-crown mr-1 text-accent-300" aria-hidden="true"></i>Super admin · all pages</span>
                    @else
                        @foreach($member->admin_permissions ?? [] as $key)
                            <span class="rounded-full bg-cream-200 px-2.5 py-1 text-xs font-medium text-brand-800">{{ \App\Models\User::ADMIN_SECTIONS[$key][0] ?? $key }}</span>
                        @endforeach
                    @endif
                    @if(! $member->is_active)
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-300">Turned off</span>
                    @elseif($member->must_change_password)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">Hasn't signed in yet</span>
                    @endif
                </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.team.edit', $member) }}" class="btn-secondary btn-sm"><i class="fas fa-pen" aria-hidden="true"></i> Edit access</a>
                        @unless($member->is(auth()->user()))
                            <form action="{{ route('admin.team.resend', $member) }}" method="POST" onsubmit="return confirm('Send {{ $member->name }} a new temporary password? Their current password will stop working.');">
                                @csrf
                                <button type="submit" class="btn-secondary btn-sm"><i class="fas fa-paper-plane" aria-hidden="true"></i> Resend sign-in</button>
                            </form>
                            <form action="{{ route('admin.team.destroy', $member) }}" method="POST" onsubmit="return confirm('Remove admin access for {{ $member->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm"><i class="fas fa-user-minus" aria-hidden="true"></i> Remove</button>
                            </form>
                        @endunless
                    </div>
            </li>
        @endforeach
    </ul>
</div>
@endsection
