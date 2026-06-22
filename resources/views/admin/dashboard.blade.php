@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Admin Dashboard')

@section('content')
<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm border border-gray-200">
            <p class="text-sm uppercase tracking-[0.18em] text-gray-500">Products</p>
            <p class="mt-4 text-4xl font-bold text-gray-900">{{ number_format($totalProducts) }}</p>
            <p class="mt-2 text-sm text-gray-500">Total items in catalog</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm border border-gray-200">
            <p class="text-sm uppercase tracking-[0.18em] text-gray-500">Orders</p>
            <p class="mt-4 text-4xl font-bold text-gray-900">{{ number_format($totalOrders) }}</p>
            <p class="mt-2 text-sm text-gray-500">Orders received so far</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm border border-gray-200">
            <p class="text-sm uppercase tracking-[0.18em] text-gray-500">Revenue</p>
            <p class="mt-4 text-4xl font-bold text-gray-900">${{ number_format($totalRevenue, 2) }}</p>
            <p class="mt-2 text-sm text-gray-500">Paid revenue to date</p>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-sm border border-gray-200">
            <p class="text-sm uppercase tracking-[0.18em] text-gray-500">Pending</p>
            <p class="mt-4 text-4xl font-bold text-gray-900">{{ number_format($pendingOrders + $pendingReviews) }}</p>
            <p class="mt-2 text-sm text-gray-500">Pending orders + reviews</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1.2fr_0.8fr] gap-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.18em] text-gray-500">Recent Orders</p>
                    <h2 class="mt-3 text-2xl font-semibold text-gray-900">Latest activity</h2>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-brand-600 font-semibold hover:text-brand-700">View all</a>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-gray-500 uppercase tracking-[0.16em]">Order</th>
                            <th class="px-4 py-3 text-gray-500 uppercase tracking-[0.16em]">Customer</th>
                            <th class="px-4 py-3 text-gray-500 uppercase tracking-[0.16em]">Status</th>
                            <th class="px-4 py-3 text-gray-500 uppercase tracking-[0.16em]">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentOrders as $order)
                            <tr>
                                <td class="px-4 py-4 text-gray-900 font-medium">#{{ $order->id }}</td>
                                <td class="px-4 py-4 text-gray-600">{{ $order->user->name ?? 'Guest' }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $order->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-gray-900">${{ number_format($order->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">No recent orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm uppercase tracking-[0.18em] text-gray-500">Top Products</p>
                    <h2 class="mt-3 text-2xl font-semibold text-gray-900">Best sellers</h2>
                </div>
                <a href="{{ route('admin.products.index') }}" class="text-brand-600 font-semibold hover:text-brand-700">Manage</a>
            </div>

            <div class="mt-6 space-y-4">
                @forelse($topProducts as $product)
                    <div class="rounded-3xl border border-gray-100 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                                <p class="text-sm text-gray-500">{{ $product->reviews_count ?? 0 }} reviews</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">${{ number_format($product->price, 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No product data available yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection