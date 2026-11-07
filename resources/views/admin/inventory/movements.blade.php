@extends('layouts.admin')

@section('page_title', 'Stock history')

@section('content')
<div class="space-y-6">
    <p class="text-sm text-gray-600 max-w-3xl">Every change to stock is recorded here: who made it, when, why, and the stock left afterwards. Entries can't be edited or deleted.</p>

    <form method="GET" action="{{ route('admin.inventory.movements') }}" class="grid grid-cols-1 gap-3 rounded-2xl border border-cream-300 bg-white p-4 sm:grid-cols-2 lg:grid-cols-[2fr_1.3fr_1fr_1fr_auto]">
        <select name="product_id" class="input-field !py-2.5" aria-label="Product">
            <option value="">All products</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected((int) request('product_id') === $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <select name="type" class="input-field !py-2.5" aria-label="Type">
            <option value="">All types</option>
            @foreach($types as $type => [$label])
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="input-field !py-2.5" aria-label="From date">
        <input type="date" name="to" value="{{ request('to') }}" class="input-field !py-2.5" aria-label="To date">
        <div class="flex gap-2">
            <button type="submit" class="btn-brand !py-2.5 flex-1">Filter</button>
            @if(request()->hasAny(['product_id', 'type', 'from', 'to']))
                <a href="{{ route('admin.inventory.movements') }}" class="btn-secondary !py-2.5" aria-label="Clear filters"><i class="fas fa-times"></i></a>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-cream-300 bg-white">
        <table class="w-full min-w-[960px] text-left text-sm">
            <thead class="bg-cream-50 text-xs uppercase tracking-[0.15em] text-gray-500">
                <tr>
                    <th scope="col" class="px-5 py-3.5 font-semibold">When</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Product</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Type</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Change</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Stock after</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">By</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-200">
                @forelse($movements as $m)
                    <tr>
                        <td class="px-5 py-3 whitespace-nowrap text-gray-600">{{ $m->created_at->format('j M Y, H:i') }}</td>
                        <td class="px-5 py-3">
                            <span class="font-semibold text-brand-900">{{ $m->product?->name ?? 'Deleted product' }}</span>
                            @if($m->variant)<span class="block text-xs text-gray-500">{{ $m->variant->color_name }}</span>@endif
                        </td>
                        <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $m->type_badge }}">{{ $m->type_label }}</span></td>
                        <td class="px-5 py-3 text-right font-semibold {{ $m->quantity_change > 0 ? 'text-emerald-700' : ($m->quantity_change < 0 ? 'text-red-700' : 'text-gray-500') }}">
                            {{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change === 0 ? '0 (' . $m->quantity . ' recorded)' : $m->quantity_change }}
                        </td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ $m->stock_after }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $m->user?->name ?? ($m->order_id ? 'Customer checkout' : 'System') }}</td>
                        <td class="px-5 py-3 text-gray-600">
                            @if($m->order)
                                <a href="{{ route('admin.orders.show', $m->order) }}" class="font-semibold text-accent-600 hover:underline">{{ $m->order->order_number }}</a>
                            @endif
                            <span class="block text-xs">{{ $m->note }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No stock changes match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $movements->links() }}
</div>
@endsection
