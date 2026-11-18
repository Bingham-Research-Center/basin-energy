<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastAnnouncement extends Model
{
    protected $fillable = ['title', 'body', 'audience', 'role_ids', 'created_by', 'published_at'];

    protected $casts = ['role_ids' => 'array', 'published_at' => 'datetime'];

    public function deliveries()
    {
        return $this->hasMany(BroadcastAnnouncementDelivery::class, 'announcement_id');
    }
}
