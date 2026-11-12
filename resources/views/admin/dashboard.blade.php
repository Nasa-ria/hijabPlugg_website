@extends('layouts.admin')

@section('page_title', 'Dashboard')

@php
    $money = fn ($v) => '₵' . number_format($v, 2);
    $ranges = ['today' => 'Today', '7d' => '7 days', '30d' => '30 days'];
    $productImage = fn ($p) => $p->main_image ? \App\Support\Img::url($p->main_image, 160) : null;
    $days = max(1, (int) round($start->diffInDays($end)));
@endphp

@section('content')
<div class="space-y-8">
    {{-- Period filter --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <p class="text-sm text-gray-500">
            {{ $start->format('j M Y') }} – {{ $end->format('j M Y') }}, compared with the previous {{ $days > 1 ? $days . ' days' : 'day' }}
        </p>
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-xl border border-cream-300 bg-white p-1" role="group" aria-label="Time period">
                @foreach($ranges as $value => $label)
                    <a href="{{ route('admin.dashboard', array_filter(['range' => $value, 'category_id' => $categoryId])) }}"
                       class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $range === $value ? 'bg-brand-900 text-white' : 'text-gray-600 hover:bg-cream-200' }}"
                       @if($range === $value) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="range" value="custom">
                @if($categoryId)<input type="hidden" name="category_id" value="{{ $categoryId }}">@endif
                <label class="sr-only" for="dash-from">From</label>
                <input id="dash-from" type="date" name="from" value="{{ $range === 'custom' ? $start->toDateString() : '' }}" required class="rounded-xl border border-cream-300 bg-white px-3 py-1.5 text-sm">
                <span class="text-gray-400 text-sm">to</span>
                <label class="sr-only" for="dash-to">To</label>
                <input id="dash-to" type="date" name="to" value="{{ $range === 'custom' ? $end->toDateString() : '' }}" required class="rounded-xl border border-cream-300 bg-white px-3 py-1.5 text-sm">
                <button type="submit" class="rounded-xl px-3 py-1.5 text-sm font-semibold {{ $range === 'custom' ? 'bg-brand-900 text-white' : 'bg-accent-500 text-white hover:bg-accent-600' }}">Apply</button>
            </form>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <input type="hidden" name="range" value="{{ $range }}">
                @if($range === 'custom')
                    <input type="hidden" name="from" value="{{ $start->toDateString() }}">
                    <input type="hidden" name="to" value="{{ $end->toDateString() }}">
                @endif
                <label class="sr-only" for="dash-category">Category</label>
                <select id="dash-category" name="category_id" onchange="this.form.submit()" class="rounded-xl border border-cream-300 bg-white px-3 py-1.5 text-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected($categoryId === $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="rounded-xl bg-accent-500 px-3 py-1.5 text-sm font-semibold text-white">Apply</button></noscript>
            </form>
        </div>
    </div>

    {{-- Summary tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <x-admin.stat-tile label="Total collected" :value="$money($now['sales'])" :change="$changes['sales']" icon="fa-sack-dollar" hint="Paid orders, incl. shipping & tax" />
        <x-admin.stat-tile label="Orders" :value="number_format($now['orders'])" :change="$changes['orders']" icon="fa-bag-shopping" hint="Paid orders" />
        <x-admin.stat-tile label="Avg. order value" :value="$money($now['aov'])" :change="$changes['aov']" icon="fa-receipt" />
        <x-admin.stat-tile label="Visitors" :value="number_format($now['visitors'])" :change="$changes['visitors']" icon="fa-user-group" :hint="number_format($now['views']) . ' page views'" />
        <x-admin.stat-tile label="Conversion" :value="number_format($now['conversion'], 1) . '%'" :change="$changes['conversion']" icon="fa-percent" hint="Orders ÷ visitors" />
    </div>

    {{-- Profit, losses, returns and stock --}}
    @include('admin._finance')

    {{-- Charts --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
            <h2 class="font-serif text-xl text-brand-900">Sales over time</h2>
            <div class="mt-4 h-64"><canvas id="salesChart" aria-label="Sales over time" role="img"></canvas></div>
        </section>
        <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
            <h2 class="font-serif text-xl text-brand-900">Visitors over time</h2>
            <div class="mt-4 h-64"><canvas id="visitorsChart" aria-label="Visitors over time" role="img"></canvas></div>
        </section>
    </div>

    {{-- Product insights --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
            <h2 class="font-serif text-xl text-brand-900">Best-selling products</h2>
            <p class="text-sm text-gray-500">Units sold and revenue, with colours</p>
            <ul class="mt-4 divide-y divide-cream-200">
                @forelse($bestSellers as $row)
                    <li class="flex items-start gap-4 py-3">
                        @if($img = $productImage($row->product))<img src="{{ $img }}" alt="" class="h-14 w-12 rounded-lg object-cover">@endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-brand-900">{{ $row->product->name }}</p>
                            <p class="mt-1 flex flex-wrap gap-1.5">
                                @foreach($row->colours as $colour)
                                    <span class="rounded-full bg-cream-200 px-2 py-0.5 text-[11px] text-brand-800">{{ $colour->colour }} · {{ $colour->units }}</span>
                                @endforeach
                            </p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-semibold text-brand-900">{{ $row->units }} sold</p>
                            <p class="text-gray-500">{{ $money($row->revenue) }}</p>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-sm text-gray-500">No paid orders in this period yet.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
            <h2 class="font-serif text-xl text-brand-900">Viewed a lot, rarely bought</h2>
            <p class="text-sm text-gray-500">At least 5 views and under 5% bought. Worth checking price or photos.</p>
            <ul class="mt-4 divide-y divide-cream-200">
                @forelse($viewedNotBought as $row)
                    <li class="flex items-center gap-4 py-3">
                        @if($img = $productImage($row->product))<img src="{{ $img }}" alt="" class="h-14 w-12 rounded-lg object-cover">@endif
                        <p class="min-w-0 flex-1 truncate font-medium text-brand-900">
                            <a href="{{ route('admin.products.edit', $row->product) }}" class="hover:text-accent-700">{{ $row->product->name }}</a>
                        </p>
                        <p class="text-right text-sm"><span class="font-semibold text-brand-900">{{ $row->views }} views</span><br><span class="text-gray-500">{{ $row->units }} sold · {{ number_format($row->rate, 1) }}%</span></p>
                    </li>
                @empty
                    <li class="py-6 text-sm text-gray-500">Nothing stands out in this period.</li>
                @endforelse
            </ul>
        </section>

        @foreach([['Most viewed products', 'Product page and popup views', $mostViewed, 'views'], ['Most clicked from the home page', 'Clicks on home page products', $mostClicked, 'clicks']] as [$title, $subtitle, $rows, $unit])
            <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
                <h2 class="font-serif text-xl text-brand-900">{{ $title }}</h2>
                <p class="text-sm text-gray-500">{{ $subtitle }}</p>
                @php $max = max(1, $rows->max('total') ?? 1); @endphp
                <ul class="mt-4 space-y-3">
                    @forelse($rows as $row)
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate text-brand-900">{{ $row->product->name }}</span>
                                <span class="font-semibold text-brand-900 whitespace-nowrap">{{ number_format($row->total) }} {{ $unit }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 rounded-full bg-cream-200"><div class="h-1.5 rounded-full bg-accent-500" style="width: {{ ($row->total / $max) * 100 }}%"></div></div>
                        </li>
                    @empty
                        <li class="py-4 text-sm text-gray-500">No {{ $unit }} recorded in this period yet.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>

    {{-- Recent orders --}}
    <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
        <div class="flex items-center justify-between">
            <h2 class="font-serif text-xl text-brand-900">Recent orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-accent-700 hover:text-accent-800">All orders <i class="fas fa-arrow-right text-xs"></i></a>
        </div>
        <ul class="mt-4 divide-y divide-cream-200">
            @forelse($recentOrders as $order)
                <li>
                    <a href="{{ route('admin.orders.show', $order) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 py-3 hover:bg-cream-50 -mx-2 px-2 rounded-lg">
                        <span class="font-medium text-brand-900">{{ $order->order_number }}</span>
                        <span class="text-sm text-gray-500">{{ $order->customer_name ?? $order->user?->name }}</span>
                        <span class="ml-auto text-sm font-semibold text-brand-900">{{ $money($order->total_amount) }}</span>
                        <x-admin.status-badge :status="$order->status" />
                    </a>
                </li>
            @empty
                <li class="py-6 text-sm text-gray-500">No orders yet.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    (function () {
        if (!window.Chart) return;
        const chart = @json($chart);
        Chart.defaults.font.family = '"Montserrat Variable", Montserrat, system-ui, sans-serif';
        Chart.defaults.color = '#6b7280';
        const options = (format, tick = (v) => Number(v).toLocaleString()) => ({
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ` ${format(c.parsed.y)}` } } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } },
                y: { beginAtZero: true, grid: { color: '#f3e9e2' }, ticks: { precision: 0, callback: (v) => tick(v) } },
            },
        });
        const line = (data, colour) => ({
            data, borderColor: colour, backgroundColor: colour + '22', fill: true,
            tension: 0.35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4,
        });

        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: { labels: chart.labels, datasets: [line(chart.salesData, '#c2506b')] },
            options: options((v) => '₵' + Number(v).toLocaleString(), (v) => '₵' + Number(v).toLocaleString()),
        });
        const profit = @json($profitChart);
        if (document.getElementById('profitChart')) {
            const money = (v) => '₵' + Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 });
            new Chart(document.getElementById('profitChart'), {
                type: 'bar',
                data: {
                    labels: profit.labels,
                    datasets: [
                        { type: 'line', label: 'Gross profit', data: profit.profit, borderColor: '#c2506b', backgroundColor: '#c2506b', tension: 0.35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, order: 0 },
                        { label: 'Revenue', data: profit.revenue, backgroundColor: '#3a1234', borderRadius: 4, order: 1 },
                        { label: 'Cost of goods', data: profit.cost, backgroundColor: '#dfc980', borderRadius: 4, order: 2 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${money(c.parsed.y)}` } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } },
                        y: { grid: { color: '#f3e9e2' }, ticks: { callback: (v) => money(v) } },
                    },
                },
            });
        }
        new Chart(document.getElementById('visitorsChart'), {
            type: 'line',
            data: { labels: chart.labels, datasets: [line(chart.visitorData, '#3a1234')] },
            options: options((v) => Number(v).toLocaleString() + ' visitors'),
        });
    })();
</script>
@endpush
