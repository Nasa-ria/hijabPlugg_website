<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Order confirmation ("processing") and every later status update share one branded template.
 */
class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public const MESSAGES = [
        'processing' => [
            'subject' => 'Thank you! Your order :number is confirmed',
            'heading' => 'Thank you for your order',
            'body' => 'We\'ve received your payment and your order is now being prepared with care. We\'ll let you know as soon as it\'s packaged.',
        ],
        'packaged' => [
            'subject' => 'Your order :number is packaged',
            'heading' => 'Your order is packaged',
            'body' => 'Your pieces have been checked, folded and wrapped. They\'ll be on their way to you very soon.',
        ],
        'out_for_delivery' => [
            'subject' => 'Your order :number is out for delivery',
            'heading' => 'Your order is on its way',
            'body' => 'Good news! Your order is out for delivery. Please keep your phone close so our rider can reach you.',
        ],
        'delivered' => [
            'subject' => 'Your order :number has been delivered',
            'heading' => 'Delivered, enjoy!',
            'body' => 'Your order has been delivered. We hope you love your new pieces. Thank you for choosing Nasaari.',
        ],
        'cancelled' => [
            'subject' => 'Your order :number has been cancelled',
            'heading' => 'Your order has been cancelled',
            'body' => 'Your order has been cancelled. If you have any questions, or this wasn\'t expected, simply reply to this email and we\'ll help.',
        ],
    ];

    public function __construct(public Order $order, public string $status)
    {
    }

    public function envelope(): Envelope
    {
        $subject = self::MESSAGES[$this->status]['subject'] ?? 'Update on your order :number';

        return new Envelope(subject: str_replace(':number', $this->order->order_number, $subject));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status',
            with: [
                'order' => $this->order,
                'status' => $this->status,
                'statusLabel' => \App\Models\Order::STATUSES[$this->status]['label'] ?? $this->status,
                'copy' => self::MESSAGES[$this->status] ?? self::MESSAGES['processing'],
                'trackUrl' => route('orders.track', ['order' => $this->order->order_number]),
            ],
        );
    }
}
