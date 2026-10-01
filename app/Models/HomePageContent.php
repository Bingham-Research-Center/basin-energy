<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomePageContent extends Model
{
    protected $table = 'home_page_contents';

    protected $fillable = [
        'section',
        'item_key',
        'title',
        'description',
        'button_text',
        'button_link',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function getSectionItem(string $section, ?string $itemKey = null)
    {
        return static::where('section', $section)
            ->when($itemKey !== null, function ($query) use ($itemKey) {
                $query->where('item_key', $itemKey);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }
}