<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Fulfilment stages in order, plus "cancelled" for exceptions.
     * badge = Tailwind classes using the site palette.
     */
    public const STATUSES = [
        'processing' => ['label' => 'Processing', 'badge' => 'bg-cream-200 text-brand-800 ring-cream-400'],
        'packaged' => ['label' => 'Packaged', 'badge' => 'bg-accent-100 text-accent-800 ring-accent-300'],
        'out_for_delivery' => ['label' => 'Out for delivery', 'badge' => 'bg-brand-100 text-brand-800 ring-brand-300'],
        'delivered' => ['label' => 'Delivered', 'badge' => 'bg-emerald-50 text-emerald-800 ring-emerald-200'],
        'cancelled' => ['label' => 'Cancelled', 'badge' => 'bg-gray-100 text-gray-600 ring-gray-300'],
    ];

    public const FLOW = ['processing', 'packaged', 'out_for_delivery', 'delivered'];

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'shipping_cost',
        'tax',
        'total_amount',
        'currency',
        'display_currency',
        'display_exchange_rate',
        'payment_fees',
        'status',
        'payment_status',
        'payment_method',
        'transaction_id',
        'shipping_address',
        'shipping_method',
        'shipping_country',
        'shipping_city',
        'shipping_postal_code',
        'shipping_estimate',
        'shipping_final_cost',
        'shipping_quote',
        'shipping_confirmed_at',
        'notes',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'stock_released_at',
    ];

    public const SHIPPING_POD = 'ghana_pay_on_delivery';
    public const SHIPPING_DHL = 'dhl_express';

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'display_exchange_rate' => 'decimal:8',
        'payment_fees' => 'decimal:2',
        'shipping_estimate' => 'decimal:2',
        'shipping_final_cost' => 'decimal:2',
        'shipping_quote' => 'array',
        'shipping_confirmed_at' => 'datetime',
        'stock_released_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Invoice / receipt number, derived from the order number (ORD-20261107… → INV-20261107…).
     */
    public function invoiceNumber(): string
    {
        return 'INV-' . preg_replace('/^ORD-/', '', $this->order_number);
    }

    public function isPayOnDelivery(): bool
    {
        return $this->shipping_method === self::SHIPPING_POD;
    }

    public function isDhl(): bool
    {
        return $this->shipping_method === self::SHIPPING_DHL;
    }

    /**
     * Customer-facing label for the shipping line.
     */
    public function shippingLabel(): string
    {
        return match ($this->shipping_method) {
            self::SHIPPING_POD => 'Delivery (paid on delivery)',
            self::SHIPPING_DHL => $this->shipping_confirmed_at ? 'DHL Express shipping' : 'DHL Express shipping (estimate)',
            default => 'Shipping',
        };
    }

    /**
     * What the customer was shown, e.g. "≈ $12.40", for orders placed with a display currency.
     */
    public function displayTotal(): ?string
    {
        if (! $this->display_currency || $this->display_currency === $this->currency || ! $this->display_exchange_rate) {
            return null;
        }

        return '≈ ' . app(\App\Services\CurrencyService::class)->formatIn((float) $this->total_amount * (float) $this->display_exchange_rate, $this->display_currency);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUSES[$this->status]['badge'] ?? 'bg-gray-100 text-gray-600 ring-gray-300';
    }

    /**
     * The next fulfilment stage, or null when delivered or cancelled.
     */
    public function nextStatus(): ?string
    {
        $index = array_search($this->status, self::FLOW, true);

        return $index === false ? null : (self::FLOW[$index + 1] ?? null);
    }

    public function contactEmail(): ?string
    {
        return $this->customer_email ?: $this->user?->email;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->order_number) {
                $model->order_number = 'ORD-' . date('YmdHis') . '-' . random_int(1000, 9999);
            }
        });
    }
}
