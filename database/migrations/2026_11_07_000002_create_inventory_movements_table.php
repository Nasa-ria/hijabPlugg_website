<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of every stock change: who, when, why, how much, and the stock left afterwards.
 * Stock of a product (or colour) at any moment = sum of quantity_change up to that moment,
 * so an "opening" row is written for the stock that exists when this table is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('type', 30);                 // see App\Models\InventoryMovement::TYPES
            $table->integer('quantity_change');         // + into stock, − out of stock, 0 = recorded only
            $table->integer('quantity');                // units concerned (always positive)
            $table->integer('stock_after');
            $table->decimal('unit_cost', 10, 2)->default(0); // purchase cost (GHS) at the time
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('product_return_id')->nullable()->constrained('product_returns')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // null = system/customer
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'variant_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        $now = now();
        $rows = [];

        foreach (DB::table('product_variants')->where('stock_quantity', '!=', 0)->get() as $variant) {
            $rows[] = [
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'type' => 'opening',
                'quantity_change' => $variant->stock_quantity,
                'quantity' => abs($variant->stock_quantity),
                'stock_after' => $variant->stock_quantity,
                'note' => 'Stock on hand when inventory tracking started',
                'created_at' => $now,
            ];
        }

        $withVariants = DB::table('product_variants')->distinct()->pluck('product_id');
        foreach (DB::table('products')->whereNotIn('id', $withVariants)->where('stock_quantity', '!=', 0)->get() as $product) {
            $rows[] = [
                'product_id' => $product->id,
                'variant_id' => null,
                'type' => 'opening',
                'quantity_change' => $product->stock_quantity,
                'quantity' => abs($product->stock_quantity),
                'stock_after' => $product->stock_quantity,
                'note' => 'Stock on hand when inventory tracking started',
                'created_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('inventory_movements')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
