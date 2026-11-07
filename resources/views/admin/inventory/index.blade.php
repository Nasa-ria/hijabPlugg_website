@extends('layouts.admin')

@section('page_title', 'Inventory')

@php $money = fn ($v) => '₵' . number_format((float) $v, 2); @endphp

@section('content')
<div class="space-y-6">
    {{-- Stock position --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-cream-300 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Inventory value</p>
            <p class="mt-3 font-serif text-3xl text-brand-900">{{ $money($position['valuation']) }}</p>
            <p class="mt-1 text-xs text-gray-500">At purchase cost · {{ $money($position['retail_value']) }} at selling price</p>
        </div>
        <div class="rounded-2xl border border-cream-300 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Units in stock</p>
            <p class="mt-3 font-serif text-3xl text-brand-900">{{ number_format($position['units_in_stock']) }}</p>
            <p class="mt-1 text-xs text-gray-500">Across all products and colours</p>
        </div>
        <a href="{{ route('admin.inventory.index', ['stock' => 'low']) }}" class="rounded-2xl border {{ $position['low_stock']->count() ? 'border-amber-300 bg-amber-50' : 'border-cream-300 bg-white' }} p-5 hover:border-amber-400">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Low stock</p>
            <p class="mt-3 font-serif text-3xl text-brand-900">{{ $position['low_stock']->count() }}</p>
            <p class="mt-1 text-xs text-gray-500">At or below their alert level</p>
        </a>
        <a href="{{ route('admin.inventory.index', ['stock' => 'out']) }}" class="rounded-2xl border {{ $position['out_of_stock']->count() ? 'border-red-200 bg-red-50' : 'border-cream-300 bg-white' }} p-5 hover:border-red-300">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Out of stock</p>
            <p class="mt-3 font-serif text-3xl text-brand-900">{{ $position['out_of_stock']->count() }}</p>
            <p class="mt-1 text-xs text-gray-500">Customers can't buy these</p>
        </a>
    </div>

    @if($position['missing_cost'])
        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-800">
            <i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>
            {{ $position['missing_cost'] }} {{ Str::plural('item', $position['missing_cost']) }} have no purchase cost yet, so profit for them is overstated. Add it on the product page or with an Excel import.
        </p>
    @endif

    {{-- Filters and actions --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="grid flex-1 grid-cols-1 gap-3 rounded-2xl border border-cream-300 bg-white p-4 sm:grid-cols-[2fr_1fr_1fr_auto]">
            <div class="relative">
                <label for="inv-q" class="sr-only">Search</label>
                <i class="fas fa-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs text-gray-400" aria-hidden="true"></i>
                <input id="inv-q" type="search" name="q" value="{{ request('q') }}" placeholder="Product, colour or SKU" class="input-field !py-2.5 pl-10">
            </div>
            <select name="category_id" class="input-field !py-2.5" aria-label="Category">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((int) request('category_id') === $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="stock" class="input-field !py-2.5" aria-label="Stock">
                <option value="">Any stock level</option>
                <option value="low" @selected(request('stock') === 'low')>Low stock</option>
                <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
                <option value="in" @selected(request('stock') === 'in')>In stock</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-brand !py-2.5 flex-1">Filter</button>
                @if(request()->hasAny(['q', 'category_id', 'stock']))
                    <a href="{{ route('admin.inventory.index') }}" class="btn-secondary !py-2.5" aria-label="Clear filters"><i class="fas fa-times"></i></a>
                @endif
            </div>
        </form>
        <div class="flex gap-2">
            <a href="{{ route('admin.inventory.import') }}" class="btn-secondary !py-2.5"><i class="fas fa-file-excel text-xs" aria-hidden="true"></i> Import / export</a>
            <a href="{{ route('admin.inventory.movements') }}" class="btn-secondary !py-2.5"><i class="fas fa-clock-rotate-left text-xs" aria-hidden="true"></i> History</a>
        </div>
    </div>

    <p class="text-sm text-gray-500">{{ $units->count() }} {{ Str::plural('item', $units->count()) }}</p>

    {{-- Stock table --}}
    <div class="overflow-x-auto rounded-2xl border border-cream-300 bg-white">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-cream-50 text-xs uppercase tracking-[0.15em] text-gray-500">
                <tr>
                    <th scope="col" class="px-5 py-3.5 font-semibold">Product</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold">SKU</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">In stock</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Cost</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Price</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold text-right">Stock value</th>
                    <th scope="col" class="px-5 py-3.5 font-semibold"><span class="sr-only">Adjust</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-200">
                @forelse($units as $unit)
                    <tr class="{{ $unit->is_out ? 'bg-red-50/40' : ($unit->is_low ? 'bg-amber-50/50' : '') }}">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.products.edit', $unit->product) }}" class="font-semibold text-brand-900 hover:text-accent-600">{{ $unit->name }}</a>
                            @if($unit->colour)<span class="block text-xs text-gray-500">{{ $unit->colour }}</span>@endif
                            @unless($unit->product->is_available)<span class="text-xs text-gray-400">Hidden from shop</span>@endunless
                        </td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $unit->sku }}</td>
                        <td class="px-5 py-3 text-right">
                            <span class="font-semibold {{ $unit->is_out ? 'text-red-700' : ($unit->is_low ? 'text-amber-700' : 'text-gray-900') }}">{{ $unit->stock }}</span>
                            @if($unit->is_out)
                                <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">Out</span>
                            @elseif($unit->is_low)
                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">Low · alert at {{ $unit->threshold }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right {{ $unit->cost <= 0 ? 'text-amber-700' : 'text-gray-700' }}">{{ $unit->cost > 0 ? $money($unit->cost) : 'Not set' }}</td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ $money($unit->price) }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-brand-900">{{ $money($unit->value) }}</td>
                        <td class="px-5 py-3 text-right">
                            <button type="button" class="btn-secondary btn-sm" data-adjust
                                    data-product="{{ $unit->product->id }}" data-variant="{{ $unit->variant?->id }}"
                                    data-name="{{ $unit->name }}{{ $unit->colour ? ' – ' . $unit->colour : '' }}" data-stock="{{ $unit->stock }}" data-cost="{{ $unit->cost }}">
                                <i class="fas fa-pen text-[10px]" aria-hidden="true"></i> Adjust
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">No products match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Adjust stock dialog --}}
<dialog id="adjustDialog" class="w-full max-w-lg rounded-3xl p-0 backdrop:bg-brand-dark/40" aria-labelledby="adjustTitle">
    <form method="POST" action="{{ route('admin.inventory.adjust') }}" class="space-y-5 p-6 sm:p-8">
        @csrf
        <input type="hidden" name="product_id">
        <input type="hidden" name="variant_id">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="adjustTitle" class="font-serif text-2xl text-brand-900">Adjust stock</h2>
                <p class="text-sm text-gray-500"><span data-adjust-name></span> · <span data-adjust-stock></span> in stock</p>
            </div>
            <button type="button" class="p-2 text-gray-400 hover:text-brand-900" data-adjust-close aria-label="Close"><i class="fas fa-times"></i></button>
        </div>

        <fieldset>
            <legend class="admin-label">What happened?</legend>
            <div class="grid grid-cols-2 gap-2">
                @foreach($manualTypes as $type => [$label])
                    <label class="flex items-center gap-2 rounded-xl border border-cream-300 px-3 py-2.5 text-sm has-[:checked]:border-brand-900 has-[:checked]:bg-cream-100">
                        <input type="radio" name="type" value="{{ $type }}" @checked($type === 'restock') class="text-accent-600 focus:ring-accent-500">
                        {{ match ($type) { 'restock' => 'Delivery / stock added', 'adjustment' => 'Count correction', 'damage' => 'Damaged in store', 'write_off' => 'Lost / write-off', default => $label } }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div data-adjust-mode class="hidden">
            <span class="admin-label">Correction</span>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="inline-flex items-center gap-2"><input type="radio" name="mode" value="set" checked class="text-accent-600"> Set the exact count</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="mode" value="change" class="text-accent-600"> Add / remove</label>
            </div>
            <div class="mt-3 hidden gap-4 text-sm" data-adjust-direction>
                <label class="inline-flex items-center gap-2"><input type="radio" name="direction" value="in" checked class="text-accent-600"> Add</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="direction" value="out" class="text-accent-600"> Remove</label>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="admin-label" for="adjust-qty" data-adjust-qty-label>Quantity</label>
                <input id="adjust-qty" type="number" name="quantity" min="0" max="100000" required class="input-field">
            </div>
            <div data-adjust-cost>
                <label class="admin-label" for="adjust-cost">Unit cost (₵)</label>
                <input id="adjust-cost" type="number" name="unit_cost" step="0.01" min="0" class="input-field">
            </div>
        </div>

        <div>
            <label class="admin-label" for="adjust-note">Note <span class="font-normal text-gray-500">(saved in the stock history)</span></label>
            <input id="adjust-note" type="text" name="note" maxlength="200" placeholder="e.g. Supplier delivery INV-204" class="input-field">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="button" class="btn-secondary flex-1" data-adjust-close>Cancel</button>
            <button type="submit" class="btn-brand flex-1">Save</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
    (function () {
        const dialog = document.getElementById('adjustDialog');
        const form = dialog.querySelector('form');
        const sync = () => {
            const type = form.querySelector('[name="type"]:checked').value;
            const mode = form.querySelector('[name="mode"]:checked').value;
            form.querySelector('[data-adjust-mode]').classList.toggle('hidden', type !== 'adjustment');
            form.querySelector('[data-adjust-direction]').classList.toggle('hidden', type !== 'adjustment' || mode !== 'change');
            form.querySelector('[data-adjust-direction]').classList.toggle('flex', type === 'adjustment' && mode === 'change');
            form.querySelector('[data-adjust-cost]').classList.toggle('invisible', type !== 'restock');
            form.querySelector('[data-adjust-qty-label]').textContent =
                type === 'adjustment' && mode === 'set' ? 'Counted stock' : (type === 'restock' ? 'Units received' : 'Units');
        };
        form.addEventListener('change', sync);
        document.querySelectorAll('[data-adjust]').forEach((button) => button.addEventListener('click', () => {
            form.product_id.value = button.dataset.product;
            form.variant_id.value = button.dataset.variant || '';
            form.unit_cost.value = Number(button.dataset.cost) > 0 ? button.dataset.cost : '';
            form.quantity.value = '';
            dialog.querySelector('[data-adjust-name]').textContent = button.dataset.name;
            dialog.querySelector('[data-adjust-stock]').textContent = button.dataset.stock;
            sync();
            dialog.showModal();
            form.quantity.focus();
        }));
        dialog.querySelectorAll('[data-adjust-close]').forEach((b) => b.addEventListener('click', () => dialog.close()));
    })();
</script>
@endpush
