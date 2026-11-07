@extends('layouts.app')

@section('title', 'Create Account – Nasaari Store')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-cream-100 via-cream-50 to-cream-200 py-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-[0.95fr_1.05fr] gap-10 items-center">
            <div class="space-y-6">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cream-200 text-brand-700 text-sm font-semibold shadow-sm shadow-brand-500/10">
                    <i class="fas fa-user-plus text-xs"></i> Create your account
                </div>
                <div>
                    <h1 class="section-heading">Start shopping faster</h1>
                    <p class="section-subheading max-w-2xl">Register and enjoy a seamless checkout experience, order tracking, and personalized recommendations.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-[1.75rem] border border-gray-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-900">Order history</p>
                        <p class="text-sm text-gray-500 mt-2">See all your past purchases at a glance.</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-gray-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-900">Faster checkout</p>
                        <p class="text-sm text-gray-500 mt-2">Save your details for quicker ordering next time.</p>
                    </div>
                </div>
            </div>

            <div class="card-panel p-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">Create your account</h2>
                <p class="text-sm text-gray-500 mb-8">Secure your profile and start ordering premium modest fashion.</p>

                <form method="POST" action="{{ route('register.post') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required placeholder="Your full name" class="input-field @error('name') border-red-500 ring-red-100 @enderror">
                        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com" class="input-field @error('email') border-red-500 ring-red-100 @enderror">
                        @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                            <input id="password" type="password" name="password" required placeholder="Create a password" class="input-field @error('password') border-red-500 ring-red-100 @enderror">
                            @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required placeholder="Confirm password" class="input-field">
                        </div>
                    </div>

                    <button type="submit" class="btn-brand w-full">Create Account</button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
