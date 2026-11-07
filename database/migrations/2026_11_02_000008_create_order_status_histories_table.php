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
        // One row per status change; also records whether the customer was emailed about it
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email_status', 20)->default('skipped'); // sent | skipped | failed
            $table->text('email_error')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        // Give existing orders a starting point in their timeline
        $now = now();
        foreach (DB::table('orders')->get(['id', 'status', 'created_at']) as $order) {
            DB::table('order_status_histories')->insert([
                'order_id' => $order->id,
                'status' => $order->status,
                'email_status' => 'skipped',
                'created_at' => $order->created_at ?? $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
