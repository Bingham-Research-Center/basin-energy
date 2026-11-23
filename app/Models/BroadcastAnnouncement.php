<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastAnnouncement extends Model
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';

    protected $fillable = ['title', 'body', 'body_format', 'status', 'audience', 'role_ids', 'created_by', 'published_at'];

    public function isEditable(): bool
    {
        return $this->status === self::DRAFT;
    }

    protected $casts = ['role_ids' => 'array', 'published_at' => 'datetime'];

    public function deliveries()
    {
        return $this->hasMany(BroadcastAnnouncementDelivery::class, 'announcement_id');
    }
}
