<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastNewsletterDelivery extends Model
{
    protected $fillable = [
        'newsletter_id', 'user_id', 'email', 'status', 'attempts', 'sent_at', 'last_error',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function newsletter()
    {
        return $this->belongsTo(BroadcastNewsletter::class, 'newsletter_id');
    }
}
