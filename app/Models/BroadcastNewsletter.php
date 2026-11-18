<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastNewsletter extends Model
{
    public const DRAFT = 'draft';
    public const SCHEDULED = 'scheduled';
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const SENT_WITH_ERRORS = 'sent_with_errors';

    protected $fillable = [
        'title', 'body', 'body_format', 'issue_month', 'status', 'scheduled_at', 'sent_at', 'created_by',
    ];

    protected $casts = [
        'issue_month' => 'date',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function deliveries()
    {
        return $this->hasMany(BroadcastNewsletterDelivery::class, 'newsletter_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::DRAFT, self::SCHEDULED], true);
    }
}
