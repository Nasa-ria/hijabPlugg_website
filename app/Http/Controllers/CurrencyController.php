<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The currency switcher: the visitor's own choice wins over country detection.
 * Display only: orders are always priced and charged in GHS.
 */
class CurrencyController extends Controller
{
    public function update(Request $request, CurrencyService $currency)
    {
        $data = $request->validate([
            'currency' => ['required', 'string', Rule::in(array_keys(config('currency.supported', [])))],
        ]);

        $currency->choose($data['currency']);

        $cookie = cookie(CurrencyService::COOKIE, $data['currency'], 60 * 24 * 365);

        if ($request->expectsJson()) {
            return response()->json(['currency' => $currency->current()])->withCookie($cookie);
        }

        $message = $currency->current() === $data['currency']
            ? 'Prices are now shown in ' . config("currency.supported.{$data['currency']}.name") . 's.'
            : 'Exchange rates are unavailable right now, so prices are shown in Ghana Cedis.';

        return back()->with('success', $message)->withCookie($cookie);
    }
}
