<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Shipping\DhlRateService;
use App\Services\Shipping\ShippingQuoteException;
use App\Support\Countries;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shipping settings overview. DHL credentials are read from .env only (never stored in
 * the database or shown here), so this page shows whether they are set and can run a test quote.
 */
class ShippingController extends Controller
{
    public function index(DhlRateService $dhl)
    {
        $dhlConfig = config('shipping.dhl');

        return view('admin.shipping.index', [
            'configured' => $dhl->isConfigured(),
            'checks' => [
                'DHL_API_KEY' => filled($dhlConfig['api_key']),
                'DHL_API_SECRET' => filled($dhlConfig['api_secret']),
                'DHL_ACCOUNT_NUMBER' => filled($dhlConfig['account_number']),
            ],
            'environment' => $dhlConfig['environment'],
            'endpoint' => $dhl->baseUrl(),
            'productCode' => $dhlConfig['product_code'],
            'origin' => config('shipping.origin'),
            'parcel' => config('shipping.parcel'),
            'domestic' => config('shipping.domestic_country'),
            'countries' => Countries::options('US'),
            'products' => Product::orderBy('name')->get(['id', 'name', 'weight_kg', 'length_cm', 'width_cm', 'height_cm']),
            'missingShippingData' => Product::where('is_available', true)->where(fn ($q) => $q->whereNull('weight_kg')->orWhereNull('length_cm')->orWhereNull('width_cm')->orWhereNull('height_cm'))->count(),
            'result' => session('dhl_test'),
        ]);
    }

    public function test(Request $request, DhlRateService $dhl)
    {
        $data = $request->validate([
            'country' => ['required', Rule::in(array_keys(Countries::LIST))],
            'city' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:50',
        ]);

        $product = Product::findOrFail($data['product_id']);

        try {
            $quote = $dhl->quote(
                ['country' => $data['country'], 'city' => $data['city'], 'postal_code' => $data['postal_code'] ?? null],
                [['product_id' => $product->id, 'quantity' => (int) $data['quantity']]],
                $product->currentPrice() * (int) $data['quantity'],
            );
        } catch (ShippingQuoteException $e) {
            return back()->withInput()->with('dhl_test', ['ok' => false, 'message' => $e->getMessage()]);
        }

        return back()->withInput()->with('dhl_test', ['ok' => true, 'quote' => $quote]);
    }
}
