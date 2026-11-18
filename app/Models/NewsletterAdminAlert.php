<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterAdminAlert extends Model
{
    protected $fillable = ['admin_id', 'subscriber_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function subscriber()
    {
        return $this->belongsTo(\App\Domains\Auth\Models\User::class, 'subscriber_id');
    }
}
