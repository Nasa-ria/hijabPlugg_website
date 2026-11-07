<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Items customers sent back. "resellable" items go back into stock;
 * "damaged" items do not, and their purchase cost is recorded as a loss.
 * Money values are GHS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('item_condition', 20); // resellable | damaged ('condition' is reserved in MySQL)
            $table->decimal('unit_price', 10, 2);  // what the customer paid per unit
            $table->decimal('unit_cost', 10, 2);   // purchase cost per unit at the time of sale
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->index('processed_at');
            $table->index(['product_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_returns');
    }
};
