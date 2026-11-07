<?php

namespace App\Support;

use App\Services\CurrencyService;

/**
 * Short helpers for views. Amounts passed in are always GHS.
 *   Money::show(120)  → "GH₵120.00" or "$7.80" (visitor's display currency)
 *   Money::ghs(120)   → "GH₵120.00"
 */
class Money
{
    public static function show(float|string|null $ghs): string
    {
        return app(CurrencyService::class)->format($ghs);
    }

    public static function ghs(float|string|null $ghs): string
    {
        return app(CurrencyService::class)->formatBase($ghs);
    }

    public static function converted(): bool
    {
        return app(CurrencyService::class)->isConverted();
    }
}
