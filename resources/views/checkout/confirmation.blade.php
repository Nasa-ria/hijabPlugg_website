@extends('layouts.app')

@section('title', 'Order Confirmation')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <div class="text-6xl text-green-500 mb-4">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="text-4xl font-bold mb-2">Order Confirmed!</h1>
        <p class="text-gray-600 text-lg">Thank you for your purchase</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        <!-- Order Details -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">Order Details</h2>

                <div class="grid grid-cols-2 gap-6 pb-6 border-b">
                    <div>
                        <p class="text-gray-600 text-sm">Order Number</p>
                        <p class="font-bold text-lg">{{ $order->order_number }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Order Date</p>
                        <p class="font-bold text-lg">{{ $order->created_at->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Order Status</p>
                        <p class="font-bold text-lg">
                            <x-admin.status-badge :status="$order->status" />
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Payment Status</p>
                        <p class="font-bold text-lg">
                            <span class="@if($order->payment_status === 'paid') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif px-3 py-1 rounded-full text-sm">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="font-bold text-lg mb-4">Shipping Address</h3>
                    <p class="text-gray-700">{{ $order->shipping_address }}</p>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4">Items</h2>

                <div class="space-y-4">
                    @foreach($order->items as $item)
                        <div class="flex gap-4 pb-4 border-b">
                            @if($item->product->main_image)
                                <img src="{{ \App\Support\Img::url($item->product->main_image, 160) }}" loading="lazy" decoding="async" alt="{{ $item->product->name }}" class="w-20 h-20 object-cover rounded">
                            @else
                                <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-500"></i>
                                </div>
                            @endif

                            <div class="flex-1">
                                <h3 class="font-bold">{{ $item->product->name }}</h3>
                                @if($item->color_name)
                                    <p class="text-gray-600">Colour: {{ $item->color_name }}</p>
                                @endif
                                <p class="text-gray-600">Quantity: {{ $item->quantity }}</p>
                                <p class="text-brand-600 font-bold">@orderMoney($item->price) each</p>
                            </div>

                            <div class="text-right">
                                <p class="font-bold text-lg">@orderMoney($item->subtotal)</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h2 class="text-2xl font-bold mb-6">Order Summary</h2>

                <x-shop.order-totals :order="$order" class="mb-6" total-class="text-xl font-bold text-brand-600" />

                @if($order->payment_status !== 'paid')
                    <a href="{{ route('payment.show', $order) }}" class="bg-accent-500 hover:bg-accent-600 block w-full text-white px-6 py-3 rounded-lg font-bold transition text-center mb-3">
                        Complete Payment
                    </a>
                @else
                    <div class="bg-green-50 border border-green-200 px-4 py-3 rounded-lg text-center mb-3">
                        <p class="text-green-800 font-bold">✓ Payment Received</p>
                    </div>
                    <x-invoice-buttons :order="$order" class="mb-4 [&>a]:flex-1" />
                @endif

                <a href="{{ route('orders.show', $order) }}" class="block w-full text-center text-brand-600 font-bold hover:underline">
                    View Full Details
                </a>

                <a href="{{ route('products.index') }}" class="block w-full text-center text-gray-600 font-bold mt-4 hover:text-brand-600">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
