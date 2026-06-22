<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subject',
        'preheader',
        'hero_text',
        'cta_text',
        'cta_url',
        'hero_image_path',
        'content',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'content' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }
}
