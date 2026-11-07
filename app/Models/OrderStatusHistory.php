<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id',
        'status',
        'changed_by',
        'email_status',
        'email_error',
        'emailed_at',
    ];

    protected $casts = [
        'emailed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return Order::STATUSES[$this->status]['label'] ?? ucfirst(str_replace('_', ' ', $this->status));
    }
}
