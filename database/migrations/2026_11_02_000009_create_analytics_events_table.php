<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Anonymous storefront activity. No names, emails or IP addresses are stored.
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);            // page_view | product_click | product_view | add_to_cart
            $table->string('visitor_id', 36);      // random anonymous ID from a cookie
            $table->string('path')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('source', 30)->nullable(); // home | shop_grid | popup | page
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
            $table->index(['product_id', 'type']);
            $table->index('visitor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
