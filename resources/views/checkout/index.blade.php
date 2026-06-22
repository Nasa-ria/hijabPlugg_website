@extends('layouts.app')

@section('title', 'Checkout – Hijabis Plugg')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">
    <div class="flex flex-col gap-4 mb-8">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-50 text-brand-600 text-sm font-semibold shadow-sm shadow-brand-500/10">
            <i class="fas fa-lock text-xs"></i> Secure checkout powered by Paystack
        </div>
        <div>
            <h1 class="section-heading">Complete Your Order</h1>
            <p class="section-subheading max-w-2xl">Review your shipping details, confirm your items, and finish checkout with confidence.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.6fr_1fr] gap-8">
        <div class="card-panel p-8">
            <div class="flex flex-col gap-2 mb-8">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-400">Shipping address</p>
                <h2 class="text-2xl font-semibold text-gray-900">Where should we deliver your order?</h2>
            </div>

            <form action="{{ route('checkout.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label for="shipping_address" class="text-sm font-semibold text-gray-700">Street Address</label>
                        <input id="shipping_address" type="text" name="shipping_address" value="{{ old('shipping_address') }}" placeholder="House number, street, suite" class="input-field @error('shipping_address') border-red-500 ring-red-100 @enderror">
                        @error('shipping_address')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-3">
                        <label for="city" class="text-sm font-semibold text-gray-700">City</label>
                        <input id="city" type="text" name="city" value="{{ old('city') }}" placeholder="City" class="input-field @error('city') border-red-500 ring-red-100 @enderror">
                        @error('city')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label for="postal_code" class="text-sm font-semibold text-gray-700">Postal Code</label>
                        <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="Postal code" class="input-field @error('postal_code') border-red-500 ring-red-100 @enderror">
                        @error('postal_code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-3">
                        <label for="phone" class="text-sm font-semibold text-gray-700">Phone Number</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="+233 XX XXX XXXX" class="input-field @error('phone') border-red-500 ring-red-100 @enderror">
                        @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-brand-100 bg-brand-50 p-6">
                    <div class="flex items-center gap-3 mb-3 text-sm text-brand-700">
                        <span class="inline-flex w-9 h-9 items-center justify-center rounded-2xl bg-brand-100 text-brand-700"><i class="fas fa-truck"></i></span>
                        <div>
                            <p class="font-semibold">Shipping is calculated at checkout</p>
                            <p class="text-sm text-brand-600">Fast delivery across Ghana with premium care.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                            <p class="font-semibold text-gray-800">Estimated Delivery</p>
                            <p>2–4 business days</p>
                        </div>
                        <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                            <p class="font-semibold text-gray-800">Secure Payments</p>
                            <p>Encrypted checkout with Paystack.</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-brand w-full text-center">Continue to Payment</button>
            </form>
        </div>

        <aside class="space-y-6">
            <div class="card-panel p-7 sticky top-8">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-400 uppercase tracking-[0.2em]">Order summary</p>
                        <h2 class="text-2xl font-semibold text-gray-900">Your cart</h2>
                    </div>
                    <span class="status-chip status-chip-pending">Review</span>
                </div>

                <div class="space-y-4 mb-6">
                    @foreach($cart as $item)
                        <div class="flex items-center gap-4 rounded-3xl border border-gray-100 bg-white p-4">
                            <div class="w-16 h-16 rounded-3xl bg-gray-100 overflow-hidden flex items-center justify-center">
                                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $item['name'] }}</p>
                                <p class="text-xs text-gray-500">Qty {{ $item['quantity'] }}</p>
                            </div>
                            <p class="ml-auto text-sm font-semibold text-gray-900">₵{{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-3 text-sm text-gray-600 border-t border-gray-100 pt-5">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-semibold text-gray-900">₵{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Shipping</span>
                        <span class="font-semibold text-gray-900">₵{{ number_format($shipping, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tax (5%)</span>
                        <span class="font-semibold text-gray-900">₵{{ number_format($tax, 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-5 border-t border-gray-100">
                    <span class="text-base font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold text-brand-600">₵{{ number_format($total, 2) }}</span>
                </div>

                <div class="mt-6 space-y-3">
                    <a href="{{ route('cart.index') }}" class="btn-secondary w-full">Back to Cart</a>
                    <p class="text-sm text-gray-500">Need to update items? Return to cart before you continue.</p>
                </div>
            </div>

            <div class="card-panel p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-gray-400 mb-4">Trusted by shoppers</p>
                <div class="space-y-3 text-sm text-gray-600">
                    <p class="flex items-center gap-3"><i class="fas fa-shield-halved text-brand-500"></i>Secure payments and verified payouts.</p>
                    <p class="flex items-center gap-3"><i class="fas fa-truck-fast text-brand-500"></i>Fast delivery with premium packaging.</p>
                    <p class="flex items-center gap-3"><i class="fas fa-headset text-brand-500"></i>Dedicated support for every order.</p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
