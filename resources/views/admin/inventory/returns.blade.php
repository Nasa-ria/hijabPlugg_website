@extends('layouts.admin')

@section('page_title', 'Returns')

@php $money = fn ($v) => '₵' . number_format((float) $v, 2); @endphp

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-admin.stat-tile :compare="false" label="Units returned" :value="number_format($totals['units'])" icon="fa-rotate-left" hint="All time" />
        <x-admin.stat-tile :compare="false" label="Refunded" :value="$money($totals['refunded'])" icon="fa-money-bill-wave" hint="Deducted from revenue" />
        <x-admin.stat-tile :compare="false" label="Damaged units" :value="number_format($totals['damaged'])" icon="fa-heart-crack" hint="Not put back into stock" />
        <x-admin.stat-tile :compare="false" label="Loss from damage" :value="$money($totals['loss'])" icon="fa-arrow-trend-down" hint="At purchase cost" />
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600">To record a return, open the order and use <strong>Record a return</strong> next to the item.</p>
        <div class="inline-flex rounded-xl border border-cream-300 bg-white p-1">
            @foreach(['' => 'All', 'resellable' => 'Resellable', 'damaged' => 'Damaged'] as $value => $label)
                <a href="{{ route('admin.inventory.returns.index', array_filter(['condition' => $value])) }}"
                   class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request('condition', '') === $value ? 'bg-brand-900 text-white' : 'text-gray-600 hover:bg-cream-200' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-cream-300 bg-white">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-cream-50 text-xs uppercase tracking-[0.15em] text-gray-500">
                <tr>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Date</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Order</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Item</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Qty</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Condition</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Refund</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Loss</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-200">
                @forelse($returns as $return)
                    <tr>
                        <td class="px-5 py-3 whitespace-nowrap text-gray-600">{{ $return->processed_at->format('j M Y') }}</td>
                        <td class="px-5 py-3"><a href="{{ route('admin.orders.show', $return->order) }}" class="font-semibold text-accent-600 hover:underline">{{ $return->order->order_number }}</a></td>
                        <td class="px-5 py-3">
                            <span class="font-semibold text-brand-900">{{ $return->product?->name }}</span>
                            @if($return->variant)<span class="block text-xs text-gray-500">{{ $return->variant->color_name }}</span>@endif
                            @if($return->reason)<span class="block text-xs text-gray-500">{{ $return->reason }}</span>@endif
                        </td>
                        <td class="px-5 py-3 text-right">{{ $return->quantity }}</td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $return->isDamaged() ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-800' }}">
                                {{ $return->isDamaged() ? 'Damaged' : 'Back in stock' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">{{ $money($return->refund_amount) }}</td>
                        <td class="px-5 py-3 text-right {{ $return->isDamaged() ? 'text-red-700' : 'text-gray-400' }}">{{ $return->isDamaged() ? $money($return->lossAmount()) : '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $return->processedBy?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-10 text-center text-gray-500">No returns recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $returns->links() }}
</div>
@endsection
