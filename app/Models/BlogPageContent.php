<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPageContent extends Model
{
    use HasFactory;

    protected $table = 'blog_page_contents';

    protected $fillable = [
        'section',
        'item_key',
        'title',
        'subtitle',
        'description',
        'image',
        'button_text',
        'button_link',
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