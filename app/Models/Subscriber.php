<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'token', 'unsubscribed_at',
    ];

    protected $dates = [
        'unsubscribed_at',
    ];

    public function isUnsubscribed()
    {
        return ! is_null($this->unsubscribed_at);
    }
}
