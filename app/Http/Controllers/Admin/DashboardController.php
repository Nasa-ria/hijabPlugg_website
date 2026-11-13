<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\Category;
use App\Services\InventoryReport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private InventoryReport $report)
    {
    }

    /**
     * Store dashboard: sales, visitors and product insights for a chosen period,
     * each compared with the period just before it.
     */
    public function index(Request $request)
    {
        [$start, $end, $range] = $this->period($request);
        $length = $start->diffInSeconds($end);
        $prevStart = $start->copy()->subSeconds($length);
        $prevEnd = $start->copy()->subSecond();

        $now = $this->totals($start, $end);
        $before = $this->totals($prevStart, $prevEnd);

        $change = fn ($key) => $before[$key] > 0 ? (($now[$key] - $before[$key]) / $before[$key]) * 100 : null;

        $hourly = $start->diffInHours($end) <= 48;
        $chart = $this->series($start, $end, $hourly);

        // Money and stock (all GHS), optionally for one category
        $request->validate(['category_id' => 'nullable|integer|exists:categories,id']);
        $categoryId = $request->integer('category_id') ?: null;
        $filters = ['category_id' => $categoryId];
        $finance = $this->report->summary($start, $end, $filters);
        $financeBefore = $this->report->summary($prevStart, $prevEnd, $filters);
        $financeChange = fn ($key) => $financeBefore[$key] > 0 ? (($finance[$key] - $financeBefore[$key]) / abs($financeBefore[$key])) * 100 : null;

        return view('admin.dashboard', [
            'categories' => Category::orderBy('name')->get(),
            'categoryId' => $categoryId,
            'finance' => $finance,
            'financeChanges' => [
                'revenue' => $financeChange('revenue'),
                'gross_profit' => $financeChange('gross_profit'),
                'losses' => $financeChange('losses'),
                'units_sold' => $financeChange('units_sold'),
                'returned_units' => $financeChange('returned_units'),
            ],
            'stock' => $this->report->stockPosition($filters),
            'profitChart' => $this->report->trend($start, $end, $hourly, $filters),
            'topMargins' => $this->report->topMargins($start, $end, $filters),
            'range' => $range,
            'start' => $start,
            'end' => $end,
            'now' => $now,
            'changes' => [
                'sales' => $change('sales'),
                'orders' => $change('orders'),
                'aov' => $change('aov'),
                'visitors' => $change('visitors'),
                'views' => $change('views'),
                'conversion' => $change('conversion'),
            ],
            'chart' => $chart,
            'mostViewed' => $this->topProducts('product_view', $start, $end),
            'mostClicked' => $this->topProducts('product_click', $start, $end, 'home'),
            'bestSellers' => $this->bestSellers($start, $end, 6, $categoryId),
            'viewedNotBought' => $this->viewedNotBought($start, $end),
            'recentOrders' => Order::with('user')->latest()->limit(8)->get(),
        ]);
    }

    /**
     * Selected period: today, 7d, 30d or a custom from/to range.
     */
    private function period(Request $request): array
    {
        $range = $request->query('range', '7d');

        if ($range === 'custom') {
            $request->validate([
                'from' => 'required|date',
                'to' => 'required|date|after_or_equal:from',
            ]);

            return [Carbon::parse($request->from)->startOfDay(), Carbon::parse($request->to)->endOfDay(), 'custom'];
        }

        return match ($range) {
            'today' => [now()->startOfDay(), now(), 'today'],
            '30d' => [now()->subDays(29)->startOfDay(), now(), '30d'],
            default => [now()->subDays(6)->startOfDay(), now(), '7d'],
        };
    }

    private function totals(Carbon $from, Carbon $to): array
    {
        $paid = Order::where('payment_status', 'paid')->whereBetween('paid_at', [$from, $to]);
        $sales = (float) (clone $paid)->sum('total_amount');
        $orders = (clone $paid)->count();

        $pageViews = AnalyticsEvent::where('type', 'page_view')->whereBetween('created_at', [$from, $to]);
        $visitors = (clone $pageViews)->distinct()->count('visitor_id');

        return [
            'sales' => $sales,
            'orders' => $orders,
            'aov' => $orders ? $sales / $orders : 0,
            'visitors' => $visitors,
            'views' => (clone $pageViews)->count(),
            'conversion' => $visitors ? ($orders / $visitors) * 100 : 0,
        ];
    }

    /**
     * Sales and visitors per day (or per hour for short periods), with empty buckets filled in.
     */
    private function series(Carbon $from, Carbon $to, bool $hourly): array
    {
        $bucket = fn (string $column) => $this->bucketSql($column, $hourly);

        $sales = Order::where('payment_status', 'paid')->whereBetween('paid_at', [$from, $to])
            ->selectRaw($bucket('paid_at') . ' as bucket, SUM(total_amount) as total')
            ->groupBy('bucket')->pluck('total', 'bucket');

        $visits = AnalyticsEvent::where('type', 'page_view')->whereBetween('created_at', [$from, $to])
            ->selectRaw($bucket('created_at') . ' as bucket, COUNT(DISTINCT visitor_id) as visitors, COUNT(*) as views')
            ->groupBy('bucket')->get()->keyBy('bucket');

        $labels = $salesData = $visitorData = $viewData = [];
        $step = $hourly ? '1 hour' : '1 day';
        foreach (CarbonPeriod::create($hourly ? $from->copy()->startOfHour() : $from->copy()->startOfDay(), $step, $to) as $point) {
            $key = $point->format($hourly ? 'Y-m-d H' : 'Y-m-d');
            $labels[] = $point->format($hourly ? 'D ga' : 'j M');
            $salesData[] = round((float) ($sales[$key] ?? 0), 2);
            $visitorData[] = (int) ($visits[$key]->visitors ?? 0);
            $viewData[] = (int) ($visits[$key]->views ?? 0);
        }

        return compact('labels', 'salesData', 'visitorData', 'viewData');
    }

    private function bucketSql(string $column, bool $hourly): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "to_char($column, '" . ($hourly ? 'YYYY-MM-DD HH24' : 'YYYY-MM-DD') . "')",
            'sqlite' => "strftime('" . ($hourly ? '%Y-%m-%d %H' : '%Y-%m-%d') . "', $column)",
            default => "DATE_FORMAT($column, '" . ($hourly ? '%Y-%m-%d %H' : '%Y-%m-%d') . "')",
        };
    }

    private function topProducts(string $type, Carbon $from, Carbon $to, ?string $source = null, int $limit = 6): Collection
    {
        $rows = AnalyticsEvent::where('type', $type)->whereNotNull('product_id')
            ->whereBetween('created_at', [$from, $to])
            ->when($source, fn ($q) => $q->where('source', $source))
            ->selectRaw('product_id, COUNT(*) as total')
            ->groupBy('product_id')->orderByDesc('total')->limit($limit)->get();

        $products = Product::withTrashed()->whereIn('id', $rows->pluck('product_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) ['product' => $products[$row->product_id] ?? null, 'total' => (int) $row->total])
            ->filter(fn ($row) => $row->product);
    }

    /**
     * Units and revenue per product from paid orders, with a per-colour breakdown.
     */
    private function bestSellers(Carbon $from, Carbon $to, int $limit = 6, ?int $categoryId = null): Collection
    {
        
        $paidItems = fn () => OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.paid_at', [$from, $to])
            ->when($categoryId, fn ($q) => $q->whereIn('order_items.product_id', Product::withTrashed()->where('category_id', $categoryId)->select('id')));

        $rows = $paidItems()
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as units, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_id')->orderByDesc('units')->limit($limit)->get();

        $colours = $paidItems()->whereIn('order_items.product_id', $rows->pluck('product_id'))
            ->selectRaw("order_items.product_id, COALESCE(order_items.color_name, '—') as colour, SUM(order_items.quantity) as units")
            ->groupBy('order_items.product_id', 'colour')->orderByDesc('units')->get()->groupBy('product_id');

        $products = Product::withTrashed()->whereIn('id', $rows->pluck('product_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'product' => $products[$row->product_id] ?? null,
            'units' => (int) $row->units,
            'revenue' => (float) $row->revenue,
            'colours' => $colours[$row->product_id] ?? collect(),
        ])->filter(fn ($row) => $row->product);
    }

    /**
     * Products people look at often but rarely buy: a hint to check price or photos.
     */
    private function viewedNotBought(Carbon $from, Carbon $to, int $minViews = 5): Collection
    {
        $views = AnalyticsEvent::where('type', 'product_view')->whereNotNull('product_id')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('product_id, COUNT(*) as views')->groupBy('product_id')
            ->havingRaw('COUNT(*) >= ?', [$minViews])->pluck('views', 'product_id');

        if ($views->isEmpty()) {
            return collect();
        }

        $units = OrderItem::query()->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')->whereBetween('orders.paid_at', [$from, $to])
            ->whereIn('order_items.product_id', $views->keys())
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as units')
            ->groupBy('order_items.product_id')->pluck('units', 'product_id');

        $products = Product::withTrashed()->whereIn('id', $views->keys())->get()->keyBy('id');

        return $views->map(fn ($count, $id) => (object) [
            'product' => $products[$id] ?? null,
            'views' => (int) $count,
            'units' => (int) ($units[$id] ?? 0),
            'rate' => ((int) ($units[$id] ?? 0) / $count) * 100,
        ])->filter(fn ($row) => $row->product && $row->rate < 5)
            ->sortBy([['rate', 'asc'], ['views', 'desc']])->take(6)->values();
    }
}
