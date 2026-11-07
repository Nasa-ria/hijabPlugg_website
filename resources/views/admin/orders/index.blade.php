@extends('layouts.admin')

@section('page_title', 'Orders')

@php $money = fn ($v) => '₵' . number_format($v, 2); @endphp

@section('content')
<div class="space-y-6">
    {{-- Search and filters --}}
    <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 gap-3 rounded-2xl border border-cream-300 bg-white p-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto]">
        <div class="relative">
            <label for="orders-q" class="sr-only">Search</label>
            <i class="fas fa-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs text-gray-400" aria-hidden="true"></i>
            <input id="orders-q" type="search" name="q" value="{{ request('q') }}" placeholder="Order number, name or email" class="input-field !py-2.5 pl-10">
        </div>
        <div>
            <label for="orders-status" class="sr-only">Status</label>
            <select id="orders-status" name="status" class="input-field !py-2.5">
                <option value="">All statuses</option>
                @foreach(\App\Models\Order::STATUSES as $value => $config)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $config['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="orders-payment" class="sr-only">Payment</label>
            <select id="orders-payment" name="payment_status" class="input-field !py-2.5">
                <option value="">Any payment</option>
                @foreach(['paid' => 'Paid', 'unpaid' => 'Unpaid', 'failed' => 'Failed'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="orders-from" class="sr-only">From date</label>
            <input id="orders-from" type="date" name="from" value="{{ request('from') }}" class="input-field !py-2.5" aria-label="From date">
        </div>
        <div>
            <label for="orders-to" class="sr-only">To date</label>
            <input id="orders-to" type="date" name="to" value="{{ request('to') }}" class="input-field !py-2.5" aria-label="To date">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-brand !py-2.5 flex-1">Filter</button>
            @if(request()->hasAny(['q', 'status', 'payment_status', 'from', 'to']))
                <a href="{{ route('admin.orders.index') }}" class="btn-secondary !py-2.5" aria-label="Clear filters"><i class="fas fa-times"></i></a>
            @endif
        </div>
    </form>

    <p class="text-sm text-gray-500">{{ $orders->total() }} {{ Str::plural('order', $orders->total()) }}</p>

    {{-- Desktop table --}}
    <div class="hidden lg:block overflow-hidden rounded-2xl border border-cream-300 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-cream-50 text-xs uppercase tracking-[0.15em] text-gray-500">
                <tr>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Order</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Date</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Customer</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Items</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Total</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Payment</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-200">
                @forelse($orders as $order)
                    <tr class="cursor-pointer hover:bg-cream-50" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                        <td class="px-5 py-4"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-brand-900 hover:text-accent-700">{{ $order->order_number }}</a></td>
                        <td class="px-5 py-4 text-gray-600 whitespace-nowrap">{{ $order->created_at->format('j M Y') }}<br><span class="text-xs text-gray-400">{{ $order->created_at->format('g:i a') }}</span></td>
                        <td class="px-5 py-4"><span class="text-brand-900">{{ $order->customer_name ?? $order->user?->name }}</span><br><span class="text-xs text-gray-500">{{ $order->contactEmail() }}</span></td>
                        <td class="px-5 py-4 text-gray-600">{{ (int) $order->units }}</td>
                        <td class="px-5 py-4 text-right font-semibold text-brand-900">{{ $money($order->total_amount) }}</td>
                        <td class="px-5 py-4"><x-admin.payment-badge :status="$order->payment_status" /></td>
                        <td class="px-5 py-4"><x-admin.status-badge :status="$order->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-500">No orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Phone cards --}}
    <ul class="lg:hidden space-y-3">
        @forelse($orders as $order)
            <li>
                <a href="{{ route('admin.orders.show', $order) }}" class="block rounded-2xl border border-cream-300 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-brand-900">{{ $order->order_number }}</p>
                            <p class="text-sm text-gray-500">{{ $order->customer_name ?? $order->user?->name }} · {{ $order->created_at->format('j M, g:i a') }}</p>
                        </div>
                        <p class="font-semibold text-brand-900">{{ $money($order->total_amount) }}</p>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <x-admin.status-badge :status="$order->status" />
                        <x-admin.payment-badge :status="$order->payment_status" />
                        <span class="text-xs text-gray-500">{{ (int) $order->units }} {{ Str::plural('item', (int) $order->units) }}</span>
                    </div>
                </a>
            </li>
        @empty
            <li class="rounded-2xl border border-cream-300 bg-white p-8 text-center text-gray-500">No orders match these filters.</li>
        @endforelse
    </ul>

    <div>{{ $orders->links() }}</div>
</div>
@endsection
