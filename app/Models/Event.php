<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'venue',
        'city',
        'cover_image',
        'flyer_image',
        'starts_at',
        'ends_at',
        'currency',
        'is_published',
        'is_featured',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('active_header_event_v1'));
        static::deleted(fn () => Cache::forget('active_header_event_v1'));
    }

    /**
     * Cached summary of the active featured/upcoming event for header nav & banner
     * (stored as a plain array for Laravel serializable_classes = false compatibility).
     *
     * @return array<string, mixed>|null
     */
    public static function cachedHeaderEvent(): ?array
    {
        try {
            if (! Schema::hasTable('events')) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        $data = Cache::remember('active_header_event_v1', 180, function (): array {
            $event = self::published()
                ->upcoming()
                ->with('activeTicketTypes')
                ->orderByDesc('is_featured')
                ->orderBy('starts_at')
                ->first();

            if (! $event) {
                return ['exists' => false];
            }

            $minPrice = $event->startingPrice();

            return [
                'exists' => true,
                'title' => $event->title,
                'slug' => $event->slug,
                'venue' => $event->venue,
                'city' => $event->city,
                'date_short' => $event->starts_at->timezone('Africa/Nairobi')->format('D, M j · g:i A'),
                'currency' => $event->currency ?: 'KES',
                'starting_price' => $minPrice,
                'cover_image_url' => $event->coverImageUrl(),
                'flyer_image_url' => $event->flyerImageUrl(),
            ];
        });

        return ($data['exists'] ?? false) ? $data : null;
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(EventTicketType::class)->orderBy('display_order')->orderBy('price');
    }

    public function activeTicketTypes(): HasMany
    {
        return $this->hasMany(EventTicketType::class)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('price');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(EventTicketOrder::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now()->subHours(6));
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now()->subHours(6));
    }

    public function isPast(): bool
    {
        return $this->starts_at !== null && $this->starts_at->lt(now()->subHours(6));
    }

    public function startingPrice(): ?float
    {
        $min = $this->relationLoaded('activeTicketTypes')
            ? $this->activeTicketTypes->min('price')
            : $this->activeTicketTypes()->min('price');

        return $min !== null ? (float) $min : null;
    }

    public function coverImageUrl(): string
    {
        if (! $this->cover_image) {
            return 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=80';
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

        return asset(ltrim($this->cover_image, '/'));
    }

    public function flyerImageUrl(): ?string
    {
        if (! $this->flyer_image) {
            return null;
        }

        if (str_starts_with($this->flyer_image, 'http://') || str_starts_with($this->flyer_image, 'https://')) {
            return $this->flyer_image;
        }

        return asset(ltrim($this->flyer_image, '/'));
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $counter = 2;

        while (self::where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
