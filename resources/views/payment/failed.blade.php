@extends('layouts.app')

@section('title', 'Payment Failed – Nasaari Store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
    
    <div class="max-w-lg mx-auto animate-fade-in">
        {{-- Error Card --}}
        <div class="card-panel p-10 text-center space-y-8">
            
            {{-- Error Icon --}}
            <div class="flex justify-center">
                <div class="w-20 h-20 rounded-full bg-red-50 border-2 border-red-200 flex items-center justify-center animate-pulse">
                    <i class="fas fa-exclamation-circle text-red-500 text-5xl"></i>
                </div>
            </div>

            {{-- Error Message --}}
            <div class="space-y-3">
                <h1 class="text-3xl font-bold text-gray-900">Payment Failed</h1>
                <p class="text-gray-600 leading-relaxed">
                    Unfortunately, your payment could not be processed. This might be due to insufficient funds, incorrect card details, or a temporary connection issue.
                </p>
            </div>

            {{-- Order Details --}}
            <div class="bg-gray-50 rounded-2xl p-6 space-y-3 text-left">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Order Number</span>
                    <span class="font-semibold text-gray-900">{{ $order->order_number }}</span>
                </div>
                <div class="flex justify-between pb-3 border-b border-gray-200">
                    <span class="text-sm text-gray-600">Amount</span>
                    <span class="font-semibold text-gray-900">@orderMoney($order->total_amount)</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Status</span>
                    <span class="status-chip-pending inline-flex items-center gap-1.5">
                        <i class="fas fa-clock text-xs"></i>
                        Awaiting Payment
                    </span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="space-y-3 pt-4">
                <a href="{{ route('payment.show', $order) }}" class="btn-brand w-full flex items-center justify-center gap-2">
                    <i class="fas fa-redo text-sm"></i>Try Payment Again
                </a>
                <a href="{{ route('orders.show', $order) }}" class="btn-secondary w-full flex items-center justify-center gap-2">
                    <i class="fas fa-eye text-sm"></i>View Order Details
                </a>
            </div>

            {{-- Help Section --}}
            <div class="border-t border-gray-200 pt-6 space-y-4">
                <p class="text-sm font-semibold text-gray-900">Need assistance?</p>
                <div class="space-y-2 text-left">
                    <div class="flex gap-3">
                        <i class="fas fa-check-circle text-emerald-500 text-sm mt-0.5 flex-shrink-0"></i>
                        <span class="text-sm text-gray-600">Your order is reserved for 1 hour</span>
                    </div>
                    <div class="flex gap-3">
                        <i class="fas fa-check-circle text-emerald-500 text-sm mt-0.5 flex-shrink-0"></i>
                        <span class="text-sm text-gray-600">No charges have been deducted</span>
                    </div>
                    <div class="flex gap-3">
                        <i class="fas fa-check-circle text-emerald-500 text-sm mt-0.5 flex-shrink-0"></i>
                        <span class="text-sm text-gray-600">Try again with a different payment method</span>
                    </div>
                </div>
            </div>

            {{-- Return Link --}}
            <div class="pt-4">
                <a href="{{ route('products.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left text-xs"></i>Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
