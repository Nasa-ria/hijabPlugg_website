@extends('layouts.app')

@section('title', 'Secure Payment – Hijabis Plugg')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">

    {{-- Page Header --}}
    <div class="mb-10">
        <h1 class="section-heading">Complete Your Payment</h1>
        <p class="section-subheading">Review your order details and proceed to secure checkout with Paystack.</p>
    </div>

    {{-- Main Layout: Two-column responsive --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-8">

        {{-- LEFT COLUMN: Payment Details Form --}}
        <div class="card-panel p-8 space-y-8 animate-slide-up">
            
            {{-- Security Header --}}
            <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-full bg-emerald-50 border border-emerald-200">
                <i class="fas fa-shield-halved text-emerald-600 text-sm"></i>
                <span class="text-xs font-semibold text-emerald-700">Secure Payment Gateway</span>
            </div>

            {{-- Order Summary Section --}}
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-receipt text-brand-500"></i>Order Summary
                </h2>
                <div class="bg-gray-50 rounded-2xl p-6 space-y-4">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                        <span class="text-sm text-gray-600">Order Number</span>
                        <span class="font-semibold text-gray-900">{{ $order->order_number }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                        <span class="text-sm text-gray-600">Order Date</span>
                        <span class="font-semibold text-gray-900">{{ $order->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Payment Gateway</span>
                        <span class="font-semibold text-gray-900">Paystack</span>
                    </div>
                </div>
            </div>

            {{-- Shipping Address Section --}}
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-map-pin text-brand-500"></i>Delivery Address
                </h2>
                <div class="card-panel bg-white p-6 border border-brand-100/50">
                    <p class="text-gray-700 leading-relaxed">{{ $order->shipping_address }}</p>
                </div>
            </div>

            {{-- Cost Breakdown --}}
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-calculator text-brand-500"></i>Price Breakdown
                </h2>
                <div class="bg-gray-50 rounded-2xl p-6 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900">₵{{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between pb-3 border-b border-gray-200">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-semibold text-gray-900">₵{{ number_format($order->shipping_cost, 2) }}</span>
                    </div>
                    <div class="flex justify-between pb-3 border-b border-gray-200">
                        <span class="text-gray-600">Tax (VAT)</span>
                        <span class="font-semibold text-gray-900">₵{{ number_format($order->tax, 2) }}</span>
                    </div>
                    <div class="flex justify-between pt-3">
                        <span class="text-lg font-bold text-gray-900">Total Amount</span>
                        <span class="text-2xl font-bold text-brand-600">₵{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Trust Signals --}}
            <div class="grid grid-cols-3 gap-3 pt-4 border-t border-gray-200">
                <div class="text-center">
                    <div class="w-10 h-10 rounded-lg bg-sky-50 flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-lock text-sky-500 text-sm"></i>
                    </div>
                    <p class="text-xs text-gray-600 font-medium">Encrypted</p>
                </div>
                <div class="text-center">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-check-circle text-emerald-500 text-sm"></i>
                    </div>
                    <p class="text-xs text-gray-600 font-medium">Verified</p>
                </div>
                <div class="text-center">
                    <div class="w-10 h-10 rounded-lg bg-brand-50 flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-shield-halved text-brand-500 text-sm"></i>
                    </div>
                    <p class="text-xs text-gray-600 font-medium">Secure</p>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Sticky CTA Section --}}
        <div class="animate-slide-up" style="animation-delay: 0.1s">
            <div class="sticky top-20 card-panel p-8 space-y-6">
                
                {{-- Payment Amount Highlight --}}
                <div class="text-center py-6 bg-gradient-to-br from-brand-50 to-brand-100/50 rounded-2xl border border-brand-100">
                    <p class="text-sm text-gray-600 mb-2">Amount Due</p>
                    <p class="text-4xl font-bold text-brand-600">₵{{ number_format($order->total_amount, 2) }}</p>
                </div>

                {{-- Payment Button --}}
                <form action="{{ route('payment.initiate', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-brand w-full py-4 text-lg font-semibold">
                        <i class="fas fa-credit-card mr-2 text-base"></i>
                        Proceed to Paystack
                    </button>
                </form>

                {{-- Alternative Action --}}
                <a href="{{ route('checkout') }}" class="btn-secondary w-full text-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Checkout
                </a>

                {{-- Help Section --}}
                <div class="bg-brand-50/50 rounded-xl p-5 border border-brand-100/50 space-y-2">
                    <p class="text-xs font-semibold text-gray-700 flex items-center gap-2">
                        <i class="fas fa-info-circle text-brand-500"></i>Need Help?
                    </p>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        If payment doesn't complete, you can return here and try again. Your order is reserved for 1 hour.
                    </p>
                </div>

                {{-- Status Indicator --}}
                <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <span class="text-xs text-gray-600">Awaiting payment confirmation</span>
                </div>
            </div>
        </div>
    </div>
</div>
