<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orders keep every amount in GHS (currency column records that explicitly).
 * display_currency/display_exchange_rate only record what the customer was shown.
 *
 * Shipping:
 *  - shipping_method: ghana_pay_on_delivery | dhl_express
 *  - shipping_cost: what the customer paid us for shipping at checkout (0 for pay on delivery)
 *  - shipping_estimate: the DHL estimate quoted at checkout
 *  - shipping_final_cost: the confirmed DHL charge, entered by an admin once shipped
 *
 * order_items.unit_cost freezes the purchase cost at the time of sale for profit reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('GHS')->after('total_amount');
            $table->string('display_currency', 3)->nullable()->after('currency');
            $table->decimal('display_exchange_rate', 18, 8)->nullable()->after('display_currency'); // display units per 1 GHS
            $table->decimal('payment_fees', 10, 2)->default(0)->after('display_exchange_rate');

            $table->string('shipping_method', 40)->nullable()->after('shipping_address');
            $table->string('shipping_country', 2)->nullable()->after('shipping_method');
            $table->string('shipping_city', 100)->nullable()->after('shipping_country');
            $table->string('shipping_postal_code', 20)->nullable()->after('shipping_city');
            $table->decimal('shipping_estimate', 10, 2)->nullable()->after('shipping_postal_code');
            $table->decimal('shipping_final_cost', 10, 2)->nullable()->after('shipping_estimate');
            $table->json('shipping_quote')->nullable()->after('shipping_final_cost');
            $table->timestamp('shipping_confirmed_at')->nullable()->after('shipping_quote');

            $table->timestamp('stock_released_at')->nullable()->after('delivered_at');

            $table->index(['payment_status', 'paid_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('unit_cost'));

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'paid_at']);
            $table->dropColumn([
                'currency', 'display_currency', 'display_exchange_rate', 'payment_fees',
                'shipping_method', 'shipping_country', 'shipping_city', 'shipping_postal_code',
                'shipping_estimate', 'shipping_final_cost', 'shipping_quote', 'shipping_confirmed_at',
                'stock_released_at',
            ]);
        });
    }
};
