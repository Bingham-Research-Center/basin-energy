<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastAnnouncementDelivery extends Model
{
    protected $fillable = [
        'announcement_id', 'user_id', 'email', 'status', 'attempts', 'sent_at', 'last_error',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function announcement()
    {
        return $this->belongsTo(BroadcastAnnouncement::class, 'announcement_id');
    }
}
