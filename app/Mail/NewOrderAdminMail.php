<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "New order" notification for the store (addresses: ADMIN_ORDER_EMAILS in .env).
 */
class NewOrderAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        $email = $this->order->contactEmail();

        return new Envelope(
            subject: 'New order #' . $this->order->order_number . ' – ₵' . number_format($this->order->total_amount, 2),
            replyTo: $email ? [new Address($email, (string) $this->order->customer_name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-order-admin', with: [
            'order' => $this->order,
            'adminUrl' => route('admin.orders.show', $this->order),
        ]);
    }
}
