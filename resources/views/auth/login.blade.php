@extends('layouts.app')

@section('title', 'Sign In – Hijabis Plugg')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-100 py-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-[1.15fr_0.85fr] gap-10 items-center">
            <div class="space-y-6">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-50 text-brand-600 text-sm font-semibold shadow-sm shadow-brand-500/10">
                    <i class="fas fa-user-circle text-xs"></i> Customer login
                </div>
                <div>
                    <h1 class="section-heading">Welcome back</h1>
                    <p class="section-subheading max-w-2xl">Sign in to manage your orders, save favorites, and enjoy faster checkout.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-[1.75rem] border border-gray-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-900">Fast order tracking</p>
                        <p class="text-sm text-gray-500 mt-2">View all your purchases in one place.</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-gray-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-900">Secure payments</p>
                        <p class="text-sm text-gray-500 mt-2">Protected checkout with trusted payment processors.</p>
                    </div>
                </div>
            </div>

            <div class="card-panel p-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">Sign in to your account</h2>
                <p class="text-sm text-gray-500 mb-8">Enter your details below to continue shopping.</p>

                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com" class="input-field @error('email') border-red-500 ring-red-100 @enderror">
                        @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <input id="password" type="password" name="password" required placeholder="Enter your password" class="input-field @error('password') border-red-500 ring-red-100 @enderror">
                        @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <label class="inline-flex items-center gap-2 text-gray-600 text-sm">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            Remember me
                        </label>
                        <a href="#" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-brand w-full">Log In</button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">New here? <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">Create an account</a>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
