@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="max-w-lg mx-auto admin-card !p-0 overflow-hidden">
        <div class="p-8">
            <h1 class="font-serif text-2xl text-brand-900 mb-4">Admin Login</h1>
            <p class="text-gray-600 mb-6">Sign in with your admin account to manage products, orders, and reviews.</p>

            <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="admin-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="input-field @error('email') border-red-500 @enderror">
                    @error('email')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="admin-label">Password</label>
                    <input type="password" name="password" required class="input-field @error('password') border-red-500 @enderror">
                    @error('password')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn-brand w-full">Sign In</button>
            </form>

            <p class="text-center text-gray-600 mt-6">Back to <a href="{{ route('login') }}" class="text-brand-600 hover:underline">customer login</a>.</p>
        </div>
    </div>
</div>
@endsection