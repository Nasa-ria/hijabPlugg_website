@extends('layouts.admin')

@php
    $editing = $member->exists;
    $selected = old('admin_permissions', $member->admin_permissions ?? []);
    $isSuper = (bool) old('is_super_admin', $member->is_super_admin);
    $descriptions = [
        'dashboard' => 'Sales, visitors and product insights',
        'orders' => 'View orders, change status, email customers',
        'messages' => 'Contact messages and feedback',
        'products' => 'Add and edit products, colours and photos',
        'categories' => 'Manage product categories',
        'reviews' => 'Approve, reply to and remove reviews',
        'newsletters' => 'Write and send newsletters',
        'posts' => 'Blog videos, tips and write-ups',
    ];
@endphp

@section('page_title', $editing ? 'Edit ' . $member->name : 'Add admin')

@section('content')
<div class="max-w-3xl space-y-6">
    <a href="{{ route('admin.team.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-accent-700 hover:text-accent-800">
        <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i> Team
    </a>

    <form action="{{ $editing ? route('admin.team.update', $member) : route('admin.team.store') }}" method="POST" class="admin-card space-y-6">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label for="member-name" class="admin-label">Name</label>
                <input id="member-name" type="text" name="name" value="{{ old('name', $member->name) }}" required maxlength="100" class="input-field">
                @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="member-email" class="admin-label">Email</label>
                <input id="member-email" type="email" name="email" value="{{ old('email', $member->email) }}" required maxlength="150" class="input-field">
                @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </div>

        <label class="flex items-start gap-3 rounded-2xl border border-cream-300 bg-cream-50 p-4 cursor-pointer has-[:checked]:border-brand-900">
            <input type="checkbox" name="is_super_admin" value="1" class="mt-1 rounded border-gray-300 text-accent-600 focus:ring-accent-500" @checked($isSuper) data-super-toggle>
            <span>
                <span class="block font-semibold text-brand-900"><i class="fas fa-crown mr-1 text-accent-500" aria-hidden="true"></i>Super admin</span>
                <span class="block text-sm text-gray-500">Can use every page and add or remove other admins.</span>
            </span>
        </label>

        <fieldset data-permissions @class(['opacity-40 pointer-events-none' => $isSuper])>
            <legend class="admin-label">Pages this admin can use</legend>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach(\App\Models\User::ADMIN_SECTIONS as $key => [$label])
                    <label class="flex items-start gap-3 rounded-xl border border-cream-300 p-3.5 cursor-pointer hover:bg-cream-50 has-[:checked]:border-accent-400 has-[:checked]:bg-accent-50/50">
                        <input type="checkbox" name="admin_permissions[]" value="{{ $key }}" class="mt-0.5 rounded border-gray-300 text-accent-600 focus:ring-accent-500" @checked(in_array($key, $selected, true))>
                        <span>
                            <span class="block text-sm font-semibold text-brand-900">{{ $label }}</span>
                            <span class="block text-xs text-gray-500">{{ $descriptions[$key] ?? '' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('admin_permissions')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </fieldset>

        @if($editing)
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-accent-600 focus:ring-accent-500" @checked(old('is_active', $member->is_active))>
                <span><strong class="text-brand-900">Account on</strong> <span class="text-gray-500">(untick to stop this admin signing in, without removing them)</span></span>
            </label>
        @else
            <p class="rounded-xl bg-cream-100 p-4 text-sm text-gray-600">
                <i class="fas fa-envelope mr-1 text-accent-600" aria-hidden="true"></i>
                We'll email them a sign-in link and a temporary password. They'll be asked to choose their own password the first time they sign in.
            </p>
        @endif

        <div class="flex flex-wrap gap-3 border-t border-cream-200 pt-6">
            <button type="submit" class="btn-brand">{{ $editing ? 'Save changes' : 'Add admin and send email' }}</button>
            <a href="{{ route('admin.team.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Super admins get every page, so the page list is only needed for regular admins
    (function () {
        const toggle = document.querySelector('[data-super-toggle]');
        const pages = document.querySelector('[data-permissions]');
        toggle.addEventListener('change', () => {
            pages.classList.toggle('opacity-40', toggle.checked);
            pages.classList.toggle('pointer-events-none', toggle.checked);
        });
    })();
</script>
@endpush
