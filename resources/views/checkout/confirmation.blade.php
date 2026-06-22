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
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                                {{ ucfirst($order->status) }}
                            </span>
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
                                <img src="{{ asset('storage/' . $item->product->main_image) }}" alt="{{ $item->product->name }}" class="w-20 h-20 object-cover rounded">
                            @else
                                <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-500"></i>
                                </div>
                            @endif

                            <div class="flex-1">
                                <h3 class="font-bold">{{ $item->product->name }}</h3>
                                <p class="text-gray-600">Quantity: {{ $item->quantity }}</p>
                                <p class="text-purple-600 font-bold">₵{{ number_format($item->price, 2) }} each</p>
                            </div>

                            <div class="text-right">
                                <p class="font-bold text-lg">₵{{ number_format($item->subtotal, 2) }}</p>
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

                <div class="space-y-3 mb-6 border-b pb-6">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span>₵{{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Shipping</span>
                        <span>₵{{ number_format($order->shipping_cost, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Tax</span>
                        <span>₵{{ number_format($order->tax, 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between mb-6 text-xl font-bold">
                    <span>Total</span>
                    <span class="text-purple-600">₵{{ number_format($order->total_amount, 2) }}</span>
                </div>

                @if($order->payment_status !== 'paid')
                    <a href="{{ route('payment.show', $order) }}" class="block w-full bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700 transition text-center mb-3">
                        Complete Payment
                    </a>
                @else
                    <div class="bg-green-50 border border-green-200 px-4 py-3 rounded-lg text-center mb-3">
                        <p class="text-green-800 font-bold">✓ Payment Received</p>
                    </div>
                @endif

                <a href="{{ route('orders.show', $order) }}" class="block w-full text-center text-purple-600 font-bold hover:underline">
                    View Full Details
                </a>

                <a href="{{ route('products.index') }}" class="block w-full text-center text-gray-600 font-bold mt-4 hover:text-purple-600">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
