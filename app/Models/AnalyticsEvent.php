<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    public const TYPES = ['page_view', 'product_click', 'product_view', 'add_to_cart'];

    public $timestamps = false;

    protected $fillable = [
        'type',
        'visitor_id',
        'path',
        'product_id',
        'variant_id',
        'source',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
