<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Analytics;
use Illuminate\Http\Request;

/**
 * Receives tiny browser pings for product clicks and popup views (sent with navigator.sendBeacon).
 */
class TrackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:product_click,product_view',
            'product_id' => 'required|integer',
            'variant_id' => 'nullable|integer',
            'source' => 'nullable|in:home,shop_grid,popup',
        ]);

        if (Product::whereKey($data['product_id'])->exists()) {
            Analytics::record($request, $data['type'], $data + ['path' => parse_url((string) $request->headers->get('referer'), PHP_URL_PATH)]);
        }

        return response()->noContent();
    }
}
