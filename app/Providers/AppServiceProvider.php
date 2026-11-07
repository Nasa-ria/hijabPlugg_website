<?php

namespace App\Providers;

use App\Services\CurrencyService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One instance per app; it re-reads the visitor's country/currency for each request
        $this->app->singleton(CurrencyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Storefront prices: @money($ghsAmount) shows the visitor's display currency (GH₵ or $).
        // The amount passed in is always GHS; conversion is for display only.
        Blade::directive('money', fn ($expression) => "<?php echo e(app(\\App\\Services\\CurrencyService::class)->format($expression)); ?>");

        // Order amounts: @orderMoney($amount) inside a view that has $order (uses the order's saved rate)
        Blade::directive('orderMoney', fn ($expression) => "<?php echo e(app(\\App\\Services\\CurrencyService::class)->formatForOrder(\$order, $expression)); ?>");

        // Always Ghana Cedis (admin pages, "you will be charged" lines, emails)
        Blade::directive('ghs', fn ($expression) => "<?php echo e(app(\\App\\Services\\CurrencyService::class)->formatBase($expression)); ?>");

        View::composer(['layouts.app', 'cart.*', 'checkout.*', 'payment.*', 'orders.*', 'products.*', 'components.shop.*'], function ($view) {
            $view->with('displayCurrency', app(CurrencyService::class));
        });
    }
}
