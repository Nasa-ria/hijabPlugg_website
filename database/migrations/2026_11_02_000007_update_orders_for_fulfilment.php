<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Customer contact details are copied onto the order at checkout
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('user_id');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_phone', 40)->nullable()->after('customer_email');
        });

        // New fulfilment statuses: processing → packaged → out_for_delivery → delivered (+ cancelled)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
        }
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 30)->default('processing')->change();
        });

        DB::table('orders')->where('status', 'pending')->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'shipped')->update(['status' => 'out_for_delivery']);

        // Fill contact details for existing orders from the customer's account
        foreach (DB::table('orders')->whereNull('customer_email')->get(['id', 'user_id']) as $order) {
            $user = DB::table('users')->where('id', $order->user_id)->first(['name', 'email', 'phone']);
            if ($user) {
                DB::table('orders')->where('id', $order->id)->update([
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->phone,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')->whereIn('status', ['packaged'])->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'out_for_delivery')->update(['status' => 'shipped']);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_email', 'customer_phone']);
        });
    }
};
