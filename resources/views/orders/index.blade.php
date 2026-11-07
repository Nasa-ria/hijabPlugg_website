@extends('layouts.app')

@section('title', 'My Orders – Nasaari Store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">
    <div class="flex flex-col gap-4 mb-10">
        <div class="inline-flex items-center gap-2 rounded-full bg-cream-200 px-4 py-2 text-sm font-semibold text-brand-700 shadow-sm shadow-brand-500/10">
            <i class="fas fa-box text-xs"></i> Order history
        </div>
        <div>
            <h1 class="section-heading">Your orders</h1>
            <p class="section-subheading max-w-2xl">Review your completed purchases, track delivery status, and revisit order details.</p>
        </div>
    </div>

    @if($orders->count() > 0)
        <div class="space-y-5">
            @foreach($orders as $order)
                <div class="card-panel p-7 hover:shadow-2xl transition-shadow duration-300">
                    <div class="grid grid-cols-1 xl:grid-cols-[1.5fr_0.9fr_0.8fr] gap-6 items-center">
                        <div class="space-y-2">
                            <p class="text-sm uppercase tracking-[0.18em] text-gray-400">Order</p>
                            <p class="font-semibold text-gray-900">{{ $order->order_number }}</p>
                            <p class="text-sm text-gray-500">Placed {{ $order->created_at->format('M d, Y') }}</p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm uppercase tracking-[0.18em] text-gray-400">Total</p>
                            <p class="text-lg font-semibold text-gray-900">@orderMoney($order->total_amount)</p>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="space-y-2">
                                <span class="status-chip {{ $order->payment_status === 'paid' ? 'status-chip-paid' : 'status-chip-pending' }}">{{ ucfirst($order->payment_status) }}</span>
                                <x-admin.status-badge :status="$order->status" />
                            </div>
                            <a href="{{ route('orders.show', $order) }}" class="btn-secondary">View details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    @else
        <div class="card-panel p-10 text-center">
            <div class="mx-auto mb-6 w-24 h-24 rounded-3xl bg-accent-50 flex items-center justify-center text-accent-500">
                <i class="fas fa-box-open text-3xl"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-900 mb-3">No orders yet</h2>
            <p class="text-gray-500 mb-6">Your order history will appear here once you place your first purchase.</p>
            <a href="{{ route('products.index') }}" class="btn-brand">Start shopping</a>
        </div>
    @endif
</div>
@endsection
