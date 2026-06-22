@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-8">
            <h1 class="text-3xl font-bold mb-4">Admin Login</h1>
            <p class="text-gray-600 mb-6">Sign in with your admin account to manage products, orders, and reviews.</p>

            <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-6">
                @csrf

                <div>
                    <label class="block font-bold mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('email') border-red-500 @enderror">
                    @error('email')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block font-bold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('password') border-red-500 @enderror">
                    @error('password')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="w-full bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700 transition">Sign In</button>
            </form>

            <p class="text-center text-gray-600 mt-6">Back to <a href="{{ route('login') }}" class="text-purple-600 hover:underline">customer login</a>.</p>
        </div>
    </div>
</div>
@endsection