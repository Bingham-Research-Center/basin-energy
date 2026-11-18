<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscription extends Model
{
    protected $fillable = ['user_id', 'subscribed_at', 'unsubscribed_at'];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Domains\Auth\Models\User::class);
    }

    public function getIsSubscribedAttribute(): bool
    {
        return $this->subscribed_at !== null && $this->unsubscribed_at === null;
    }
}
