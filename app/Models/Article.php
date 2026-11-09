<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'summary',
        'content',
        'featured_image',
        'image_caption',
        'image_credit',
        'user_id',
        'category_id',
        'subcategory_id',
        'article_type',
        'status',
        'scheduled_at',
        'published_at',
        'is_featured',
        'is_breaking',
        'breaking_until',
        'is_editors_pick',
        'view_count',
        'reading_time_minutes',
        'video_url',
        'meta_title',
        'meta_description',
        'canonical_url',
        'allow_comments',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'breaking_until' => 'datetime',
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'is_editors_pick' => 'boolean',
            'allow_comments' => 'boolean',
            'view_count' => 'integer',
            'reading_time_minutes' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coAuthors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'article_authors')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'article_media')
            ->withPivot(['is_featured', 'display_order'])
            ->withTimestamps();
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->orderByDesc('id');
    }

    public function editorialReviews(): HasMany
    {
        return $this->hasMany(EditorialReview::class)->orderByDesc('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('status', 'approved')->whereNull('parent_id')->orderByDesc('id');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderByDesc('id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(ArticleView::class);
    }

    public function breakingNews(): HasOne
    {
        return $this->hasOne(BreakingNews::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeBreaking(Builder $query): Builder
    {
        return $query->where('is_breaking', true)
            ->where(function ($q) {
                $q->whereNull('breaking_until')
                    ->orWhere('breaking_until', '>', now());
            });
    }

    public function scopeEditorsPick(Builder $query): Builder
    {
        return $query->where('is_editors_pick', true);
    }

    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId)
                ->orWhere('subcategory_id', $categoryId);
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('subtitle', 'like', "%{$term}%")
                ->orWhere('summary', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%");
        });
    }

    /**
     * Increment views smoothly.
     */
    public function recordView(?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->increment('view_count');

        $this->views()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'viewed_at' => now(),
        ]);
    }
}
