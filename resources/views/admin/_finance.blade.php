{{--
    Dashboard: profit, losses, returns and stock for the selected period (and category).
    All GHS. Formulas: App\Services\InventoryReport.
--}}
@php
    $f = $finance;
    $exportParams = array_filter(['from' => $start->toDateString(), 'to' => $end->toDateString(), 'category_id' => $categoryId]);
@endphp

<section aria-labelledby="finance-heading" class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 id="finance-heading" class="font-serif text-2xl text-brand-900">Profit &amp; inventory</h2>
            <p class="text-sm text-gray-500">
                Products only: shipping, tax and payment fees are shown separately.
                @if($categoryId) Showing {{ $categories->firstWhere('id', $categoryId)?->name }}. @endif
            </p>
        </div>
        @if(auth()->user()->canAccessAdmin('inventory'))
            <a href="{{ route('admin.inventory.export', $exportParams) }}" class="btn-secondary btn-sm"><i class="fas fa-file-excel text-xs" aria-hidden="true"></i> Export this period</a>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-admin.stat-tile label="Revenue" :value="$money($f['revenue'])" :change="$financeChanges['revenue']" icon="fa-coins"
            :hint="'Sales ' . $money($f['gross_sales']) . ($f['refunds'] > 0 ? ' − refunds ' . $money($f['refunds']) : '')" />
        <x-admin.stat-tile label="Gross profit" :value="$money($f['gross_profit'])" :change="$financeChanges['gross_profit']" icon="fa-chart-line"
            :hint="'Cost of goods ' . $money($f['cogs']) . ($f['margin'] !== null ? ' · ' . $f['margin'] . '% margin' : '')" />
        <x-admin.stat-tile label="Losses" :value="$money($f['losses'])" :change="$financeChanges['losses']" :good-when-up="false" icon="fa-heart-crack"
            :hint="$f['loss_units'] . ' damaged / written-off units'" />
        <x-admin.stat-tile label="Net profit" :value="$money($f['net_profit'])" :compare="false" icon="fa-scale-balanced"
            :hint="'After losses' . ($f['order_level'] ? ' and ' . $money($f['payment_fees']) . ' Paystack fees' : '')" />
        <x-admin.stat-tile label="Inventory value" :value="$money($stock['valuation'])" :compare="false" icon="fa-warehouse"
            :hint="number_format($stock['units_in_stock']) . ' units in stock, at cost'" />
        <x-admin.stat-tile label="Products sold" :value="number_format($f['units_sold'])" :change="$financeChanges['units_sold']" icon="fa-bag-shopping"
            :hint="$f['orders'] . ' paid ' . Str::plural('order', $f['orders'])" />
        <x-admin.stat-tile label="Returned" :value="number_format($f['returned_units'])" :change="$financeChanges['returned_units']" :good-when-up="false" icon="fa-rotate-left"
            :hint="$money($f['returned_value']) . ' refunded · ' . $f['restocked_units'] . ' back in stock'" />
        <a href="{{ route('admin.inventory.index', ['stock' => 'low']) }}" class="block rounded-2xl border {{ $stock['low_stock']->count() ? 'border-amber-300 bg-amber-50' : 'border-cream-300 bg-white' }} p-5 hover:border-amber-400">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Low stock</p>
                <i class="fas fa-triangle-exclamation text-amber-500" aria-hidden="true"></i>
            </div>
            <p class="mt-3 font-serif text-3xl text-brand-900">{{ $stock['low_stock']->count() }}</p>
            <p class="mt-2 text-xs text-gray-500">{{ $stock['out_of_stock']->count() }} out of stock</p>
        </a>
    </div>

    @if($f['order_level'])
        <dl class="grid grid-cols-2 lg:grid-cols-4 gap-3 rounded-2xl border border-cream-300 bg-white p-4 text-sm">
            <div><dt class="text-gray-500">DHL shipping collected</dt><dd class="font-semibold text-brand-900">{{ $money($f['shipping_collected']) }}</dd></div>
            <div>
                <dt class="text-gray-500">Paid to DHL (confirmed)</dt>
                <dd class="font-semibold text-brand-900">{{ $money($f['shipping_paid']) }}
                    @if($f['shipping_unconfirmed'])<span class="block text-xs font-normal text-amber-700">{{ $f['shipping_unconfirmed'] }} not confirmed yet</span>@endif
                </dd>
            </div>
            <div><dt class="text-gray-500">Tax collected</dt><dd class="font-semibold text-brand-900">{{ $money($f['tax_collected']) }}</dd></div>
            <div><dt class="text-gray-500">Paystack fees</dt><dd class="font-semibold text-brand-900">{{ $money($f['payment_fees']) }}</dd></div>
        </dl>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-[1.5fr_1fr] gap-6">
        <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-serif text-xl text-brand-900">Revenue, cost &amp; profit</h3>
                <p class="flex gap-4 text-xs text-gray-500">
                    <span><span class="inline-block h-2 w-3 rounded-sm bg-[#3a1234] align-middle"></span> Revenue</span>
                    <span><span class="inline-block h-2 w-3 rounded-sm bg-[#dfc980] align-middle"></span> Cost of goods</span>
                    <span><span class="inline-block h-2 w-3 rounded-sm bg-[#c2506b] align-middle"></span> Gross profit</span>
                </p>
            </div>
            <div class="mt-4 h-64"><canvas id="profitChart" aria-label="Revenue, cost of goods and profit over time" role="img"></canvas></div>
        </section>

        <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
            <h3 class="font-serif text-xl text-brand-900">Highest profit margins</h3>
            <p class="text-sm text-gray-500">Products sold in this period</p>
            <ul class="mt-4 divide-y divide-cream-200">
                @forelse($topMargins as $row)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-brand-900">{{ $row->name }}</span>
                            <span class="text-xs text-gray-500">{{ $row->quantity_sold }} sold · profit {{ $money($row->profit) }}</span>
                        </span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $row->margin >= 40 ? 'bg-emerald-50 text-emerald-800' : ($row->margin >= 15 ? 'bg-cream-200 text-brand-800' : 'bg-red-50 text-red-700') }}">{{ $row->margin }}%</span>
                    </li>
                @empty
                    <li class="py-6 text-sm text-gray-500">No sales with a purchase cost in this period yet.</li>
                @endforelse
            </ul>
        </section>
    </div>

    @if($stock['low_stock']->isNotEmpty() || $stock['out_of_stock']->isNotEmpty())
        <section class="rounded-2xl border border-amber-200 bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <h3 class="font-serif text-xl text-brand-900">Restock soon</h3>
                <a href="{{ route('admin.inventory.index', ['stock' => 'low']) }}" class="text-sm font-semibold text-accent-700 hover:text-accent-800">Inventory <i class="fas fa-arrow-right text-xs"></i></a>
            </div>
            <ul class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-x-6 divide-y divide-cream-200 md:divide-y-0">
                @foreach($stock['out_of_stock']->concat($stock['low_stock'])->take(10) as $unit)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span class="truncate text-brand-900">{{ $unit->name }}@if($unit->colour) <span class="text-gray-500">– {{ $unit->colour }}</span>@endif</span>
                        <span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $unit->is_out ? 'bg-red-50 text-red-700' : 'bg-amber-100 text-amber-800' }}">{{ $unit->is_out ? 'Out of stock' : $unit->stock . ' left' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</section>
