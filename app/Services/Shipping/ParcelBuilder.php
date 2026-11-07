<?php

namespace App\Services\Shipping;

use App\Models\Product;

/**
 * Turns cart lines into DHL packages.
 *
 * Each unit uses its product's weight and folded dimensions. Units are stacked in a box:
 * length/width = the largest unit, height = the stacked heights, plus padding and packaging
 * weight. A new box starts when the stack would exceed the configured height or weight.
 * DHL then applies its own volumetric-weight rule to these real dimensions.
 */
class ParcelBuilder
{
    /**
     * @param  array<int, array{product_id:int, quantity:int}>  $lines
     * @return array{packages: array<int, array{weight: float, length: float, width: float, height: float}>, actual_weight: float, volumetric_weight: float, chargeable_weight: float, missing_data: string[]}
     */
    public function build(array $lines): array
    {
        $config = config('shipping.parcel');
        $products = Product::withTrashed()->whereIn('id', array_column($lines, 'product_id'))->get()->keyBy('id');

        $units = [];
        $missing = [];
        foreach ($lines as $line) {
            $product = $products[$line['product_id']] ?? null;
            if (! $product) {
                continue;
            }
            if (! $product->hasShippingData()) {
                $missing[] = $product->name;
            }
            [$l, $w, $h] = $product->shippingDimensions();
            for ($i = 0; $i < (int) $line['quantity']; $i++) {
                $units[] = ['weight' => $product->shippingWeightKg(), 'length' => $l, 'width' => $w, 'height' => $h];
            }
        }

        $packages = [];
        $box = null;
        foreach ($units as $unit) {
            $fitsHeight = $box && ($box['height'] + $unit['height']) <= $config['max_package_height_cm'];
            $fitsWeight = $box && ($box['weight'] + $unit['weight']) <= $config['max_package_weight_kg'];

            if (! $box || ! $fitsHeight || ! $fitsWeight) {
                if ($box) {
                    $packages[] = $box;
                }
                $box = ['weight' => 0.0, 'length' => 0.0, 'width' => 0.0, 'height' => 0.0];
            }

            $box['weight'] += $unit['weight'];
            $box['length'] = max($box['length'], $unit['length']);
            $box['width'] = max($box['width'], $unit['width']);
            $box['height'] += $unit['height'];
        }
        if ($box) {
            $packages[] = $box;
        }

        $padding = (float) $config['packaging_padding_cm'];
        $packages = array_map(fn ($p) => [
            'weight' => round($p['weight'] + (float) $config['packaging_weight_kg'], 3),
            'length' => (float) ceil($p['length'] + $padding),
            'width' => (float) ceil($p['width'] + $padding),
            'height' => (float) ceil($p['height'] + $padding),
        ], $packages);

        $divisor = max(1, (int) $config['volumetric_divisor']);
        $actual = array_sum(array_column($packages, 'weight'));
        $volumetric = array_sum(array_map(fn ($p) => ($p['length'] * $p['width'] * $p['height']) / $divisor, $packages));
        $chargeable = array_sum(array_map(fn ($p) => max($p['weight'], ($p['length'] * $p['width'] * $p['height']) / $divisor), $packages));

        return [
            'packages' => $packages,
            'actual_weight' => round($actual, 3),
            'volumetric_weight' => round($volumetric, 3),
            'chargeable_weight' => round($chargeable, 3),
            'missing_data' => array_values(array_unique($missing)),
        ];
    }
}
