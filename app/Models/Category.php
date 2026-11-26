<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'featured_image',
        'display_order',
        'is_active',
        'show_in_menu',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('nav_categories_menu_v2');
        });

        static::deleted(function (): void {
            Cache::forget('nav_categories_menu_v2');
        });
    }

    /**
     * Cached navigation categories with active children (stored as plain arrays
     * for compatibility with Laravel's serializable_classes = false setting).
     *
     * @return Collection<int, self>
     */
    public static function cachedNavCategories(): Collection
    {
        $rows = Cache::remember('nav_categories_menu_v2', 600, function (): array {
            return self::roots()
                ->menu()
                ->with(['children' => fn ($q) => $q->menu()])
                ->get()
                ->map(fn (self $category): array => [
                    'attributes' => $category->getAttributes(),
                    'children' => $category->children->map(fn (self $child): array => $child->getAttributes())->all(),
                ])
                ->all();
        });

        $hydrated = array_map(function (array $item): self {
            $category = (new self)->newFromBuilder($item['attributes']);
            $children = self::hydrate($item['children']);
            $category->setRelation('children', $children);

            return $category;
        }, $rows);

        return new Collection($hydrated);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function publishedArticles(): HasMany
    {
        return $this->hasMany(Article::class)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function editors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_category_access')
            ->withPivot(['can_approve', 'can_publish'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeMenu(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('show_in_menu', true)->orderBy('display_order');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
