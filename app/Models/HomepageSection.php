<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HomepageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'section_type',
        'category_id',
        'display_order',
        'is_active',
        'article_count',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'article_count' => 'integer',
            'settings' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'homepage_section_articles')
            ->withPivot('display_order')
            ->orderByPivot('display_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }
}
