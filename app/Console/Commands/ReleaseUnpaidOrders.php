<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\InventoryService;
use App\Services\OrderStatusService;
use Illuminate\Console\Command;

/**
 * Unpaid orders hold their stock so nobody else can buy the same units while the customer pays.
 * After INVENTORY_RELEASE_UNPAID_AFTER_HOURS (default 48) the order is cancelled and the stock released.
 * If a payment still arrives later, PaymentController takes the stock again.
 */
class ReleaseUnpaidOrders extends Command
{
    protected $signature = 'orders:release-unpaid {--hours= : Override the configured number of hours}';

    protected $description = 'Cancel unpaid orders past the payment window and put their stock back';

    public function handle(InventoryService $inventory, OrderStatusService $statuses): int
    {
        $hours = (int) ($this->option('hours') ?? config('shop.inventory.release_unpaid_after_hours', 48));
        if ($hours <= 0) {
            $this->info('Releasing unpaid orders is turned off.');

            return self::SUCCESS;
        }

        $released = 0;
        Order::where('payment_status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->whereNull('stock_released_at')
            ->where('created_at', '<', now()->subHours($hours))
            ->orderBy('id')
            ->each(function (Order $order) use ($inventory, $statuses, &$released) {
                if ($inventory->releaseOrder($order, "Unpaid for more than the payment window: order {$order->order_number} cancelled")) {
                    $statuses->changeStatus($order, 'cancelled', notify: false);
                    $released++;
                }
            });

        $this->info("Released stock from {$released} unpaid " . str('order')->plural($released) . '.');

        return self::SUCCESS;
    }
}
