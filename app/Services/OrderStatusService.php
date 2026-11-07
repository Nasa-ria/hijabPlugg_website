<?php

namespace App\Services;

use App\Mail\NewOrderAdminMail;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Every order status change goes through here, so each change is recorded in
 * the order's history and the customer is emailed at most once per status.
 */
class OrderStatusService
{
    /**
     * Called once payment succeeds: the order is confirmed as "processing"
     * and the customer receives their order confirmation.
     */
    public function confirmPaidOrder(Order $order): OrderStatusHistory
    {
        $order->update(['status' => 'processing']);

        $entry = $order->statusHistory()->reorder()->where('status', 'processing')->latest('id')->first()
            ?? $order->statusHistory()->create(['status' => 'processing']);

        $this->email($entry);
        $this->notifyStore($order);

        return $entry;
    }

    /**
     * Email the store about a new order, once per order. The order is "claimed" first,
     * so two payment callbacks at the same moment can't both send it.
     * A failure is logged and never affects the order or the customer's email.
     */
    public function notifyStore(Order $order): bool
    {
        $recipients = config('nasaari.admin_order_emails', []);
        if (empty($recipients)) {
            return false;
        }

        $claimed = Order::whereKey($order->id)->whereNull('admin_notified_at')->update(['admin_notified_at' => now()]);
        if (! $claimed) {
            return false; // already sent
        }

        try {
            Mail::to($recipients)->send(new NewOrderAdminMail($order->fresh(['items.product', 'items.variant', 'user'])));

            return true;
        } catch (\Throwable $e) {
            Order::whereKey($order->id)->update(['admin_notified_at' => null]); // allow a later retry
            Log::error('New order email to the store failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Change the status, record it with a timestamp and (optionally) email the customer.
     */
    public function changeStatus(Order $order, string $status, bool $notify, ?User $by = null): OrderStatusHistory
    {
        $dates = match ($status) {
            'out_for_delivery' => ['shipped_at' => now()],
            'delivered' => ['delivered_at' => now()],
            default => [],
        };
        $order->update(['status' => $status] + $dates);

        $entry = $order->statusHistory()->create([
            'status' => $status,
            'changed_by' => $by?->id,
        ]);

        if ($notify) {
            $this->email($entry);
        }

        return $entry;
    }

    /**
     * Has the customer already been emailed about this status?
     */
    public function alreadyEmailed(Order $order, string $status): bool
    {
        return $order->statusHistory()->where('status', $status)->where('email_status', 'sent')->exists();
    }

    /**
     * Send (or resend) the email for one history entry. Never sends twice for the same status.
     * Returns true when the email was sent.
     */
    public function email(OrderStatusHistory $entry): bool
    {
        $order = $entry->order->loadMissing(['items.product', 'items.variant', 'user']);

        if ($this->alreadyEmailed($order, $entry->status)) {
            if ($entry->email_status !== 'sent') {
                $entry->update(['email_status' => 'skipped', 'email_error' => 'Already emailed for this status.']);
            }

            return false;
        }

        $to = $order->contactEmail();
        if (! $to) {
            $entry->update(['email_status' => 'failed', 'email_error' => 'No customer email address on this order.']);

            return false;
        }

        try {
            Mail::to($to)->send(new OrderStatusMail($order, $entry->status));
            $entry->update(['email_status' => 'sent', 'email_error' => null, 'emailed_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Order status email failed', [
                'order' => $order->order_number,
                'status' => $entry->status,
                'error' => $e->getMessage(),
            ]);
            $entry->update(['email_status' => 'failed', 'email_error' => mb_substr($e->getMessage(), 0, 500)]);

            return false;
        }
    }
}
