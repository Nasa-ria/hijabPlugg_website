<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Inventory and shipping data on products and their colours:
 *  - sku: stock-keeping code used in Excel exports/imports
 *  - cost_price: what one unit costs the store (GHS), kept apart from the selling price
 *  - low_stock_threshold: alert level (null = config('inventory.low_stock_threshold'))
 *  - weight/dimensions: used for DHL rate requests (actual + volumetric weight)
 * A colour may override the product's SKU and purchase cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 64)->nullable()->after('slug');
            $table->decimal('cost_price', 10, 2)->default(0)->after('sale_price');
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock_quantity');
            $table->decimal('weight_kg', 8, 3)->nullable()->after('low_stock_threshold');
            $table->decimal('length_cm', 8, 2)->nullable()->after('weight_kg');
            $table->decimal('width_cm', 8, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 8, 2)->nullable()->after('width_cm');
            $table->index(['is_available', 'stock_quantity']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku', 64)->nullable()->after('color_name');
            $table->decimal('cost_price', 10, 2)->nullable()->after('price'); // null = the product's cost
        });

        // Give every existing product and colour a SKU, then make SKUs unique
        foreach (DB::table('products')->orderBy('id')->get(['id']) as $product) {
            DB::table('products')->where('id', $product->id)->update(['sku' => sprintf('NSR-%05d', $product->id)]);
        }
        foreach (DB::table('product_variants')->orderBy('id')->get(['id', 'product_id', 'color_name']) as $variant) {
            $code = Str::upper(Str::limit(Str::slug($variant->color_name, ''), 8, '')) ?: 'C';
            DB::table('product_variants')->where('id', $variant->id)->update([
                'sku' => sprintf('NSR-%05d-%s-%d', $variant->product_id, $code, $variant->id),
            ]);
        }

        Schema::table('products', fn (Blueprint $table) => $table->unique('sku'));
        Schema::table('product_variants', fn (Blueprint $table) => $table->unique('sku'));
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropColumn(['sku', 'cost_price']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropIndex(['is_available', 'stock_quantity']);
            $table->dropColumn(['sku', 'cost_price', 'low_stock_threshold', 'weight_kg', 'length_cm', 'width_cm', 'height_cm']);
        });
    }
};
