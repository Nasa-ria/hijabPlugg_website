@extends('layouts.app')

@section('title', 'Order Details – Nasaari Store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">

    {{-- Page Header --}}
    <div class="mb-10 flex items-center justify-between">
        <div>
            <h1 class="section-heading">Order Details</h1>
            <p class="section-subheading">Track your order and view complete information.</p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-invoice-buttons :order="$order" size="sm" />
            <a href="{{ route('orders.index') }}" class="btn-secondary text-sm hidden sm:inline-flex">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-8">

        {{-- LEFT COLUMN: Order Items & Details --}}
        <div class="space-y-8 animate-slide-up">

            {{-- Order Info Header --}}
            <div class="card-panel p-8 space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">Order Number</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $order->order_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">Order Date</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $order->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Delivery Address --}}
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-map-pin text-accent-500"></i>Delivery Address
                </h2>
                <div class="card-panel p-8 bg-gradient-to-br from-cream-100/60 to-white border border-brand-100/50">
                    <p class="text-gray-700 leading-relaxed text-[15px]">{{ $order->shipping_address }}</p>
                </div>
            </div>

            {{-- Order Items --}}
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-shopping-bag text-accent-500"></i>Items Ordered
                </h2>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                        <div class="card-panel p-6 hover:shadow-lg transition-shadow duration-300 flex gap-6">
                            {{-- Product Image --}}
                            <div class="flex-shrink-0">
                                @if($item->product->main_image)
                                    <img src="{{ \App\Support\Img::url($item->product->main_image, 160) }}" loading="lazy" decoding="async" 
                                         alt="{{ $item->product->name }}" 
                                         class="w-24 h-24 object-cover rounded-xl">
                                @else
                                    <div class="w-24 h-24 bg-gray-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-xl"></i>
                                    </div>
                                @endif
                            </div>

                            {{-- Product Details --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-1">{{ $item->product->name }}</h3>
                                @if($item->color_name)
                                    <p class="text-xs text-gray-500 mb-1">Colour: {{ $item->color_name }}</p>
                                @endif
                                <div class="flex flex-wrap gap-3 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-boxes text-gray-400 mr-1"></i>Qty: <span class="font-semibold">{{ $item->quantity }}</span></span>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-sm text-gray-500">Price per item</span>
                                    <span class="font-semibold text-gray-900">@orderMoney($item->price)</span>
                                </div>
                            </div>

                            {{-- Subtotal --}}
                            <div class="text-right flex-shrink-0">
                                <p class="text-xs text-gray-500 mb-1">Subtotal</p>
                                <p class="text-xl font-bold text-brand-600">@orderMoney($item->subtotal)</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Sticky Order Summary & Status --}}
        <div class="animate-slide-up" style="animation-delay: 0.1s">
            <div class="sticky top-20 space-y-6">

                {{-- Order Summary --}}
                <div class="card-panel p-8 space-y-6">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-calculator text-accent-500"></i>Order Summary
                    </h2>

                    <x-shop.order-totals :order="$order" />
                </div>

                {{-- Order Status --}}
                <div class="card-panel p-8 space-y-6">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-info-circle text-accent-500"></i>Order Status
                    </h2>

                    <div class="space-y-4">
                        {{-- Order Status --}}
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Order Status</p>
                            <div class="flex items-center gap-2">
                                <x-admin.status-badge :status="$order->status" />
                                <a href="{{ route('orders.track', ['order' => $order->order_number]) }}" class="text-xs font-semibold text-accent-700 hover:underline">Track</a>
                            </div>
                        </div>

                        {{-- Payment Status --}}
                        <div class="space-y-2 pt-3 border-t border-gray-200">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Payment Status</p>
                            <div class="flex items-center gap-2">
                                @if($order->payment_status === 'paid')
                                    <span class="status-chip-paid">
                                        <i class="fas fa-check-circle text-xs mr-1.5"></i>Paid
                                    </span>
                                @elseif($order->payment_status === 'pending')
                                    <span class="status-chip-pending">
                                        <i class="fas fa-clock text-xs mr-1.5"></i>Pending
                                    </span>
                                @elseif($order->payment_status === 'failed')
                                    <span class="status-chip-cancelled">
                                        <i class="fas fa-times-circle text-xs mr-1.5"></i>Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                        <i class="fas fa-circle text-[6px]"></i>{{ ucfirst($order->payment_status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="space-y-3">
                    @if($order->payment_status === 'pending')
                        <a href="{{ route('payment.show', $order) }}" class="btn-brand w-full text-center">
                            <i class="fas fa-credit-card mr-2"></i>Complete Payment
                        </a>
                    @endif
                    <a href="{{ route('orders.index') }}" class="btn-secondary w-full text-center sm:hidden">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
