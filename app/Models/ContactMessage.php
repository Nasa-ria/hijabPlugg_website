<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const TOPICS = [
        'question' => 'General question',
        'feedback' => 'Feedback',
        'order' => 'Help with an order',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'topic',
        'order_number',
        'rating',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'rating' => 'integer',
    ];

    public function getTopicLabelAttribute(): string
    {
        return self::TOPICS[$this->topic] ?? ucfirst($this->topic);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
