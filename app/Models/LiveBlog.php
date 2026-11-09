<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveBlog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'status',
        'article_id',
        'cover_image',
        'is_live',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'is_live' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(LiveBlogUpdate::class)->orderByDesc('is_pinned')->orderByDesc('published_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_live', true)->orderByDesc('id');
    }
}
