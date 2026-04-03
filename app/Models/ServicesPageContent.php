<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicesPageContent extends Model
{
    use HasFactory;

    protected $table = 'services_page_contents';

    protected $fillable = [
        'section',
        'item_key',
        'title',
        'subtitle',
        'description',
        'icon',
        'value',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'value' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeSection($query, $section)
    {
        return $query->where('section', $section)->orderBy('sort_order');
    }

    public static function getSectionItem($section, $itemKey = 'main')
    {
        return static::where('section', $section)
            ->where('item_key', $itemKey)
            ->first();
    }
}