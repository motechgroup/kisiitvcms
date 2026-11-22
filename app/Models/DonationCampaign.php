<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DonationCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'target_amount',
        'currency',
        'cover_image',
        'start_date',
        'end_date',
        'is_active',
        'is_featured',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class, 'campaign_id');
    }

    public function completedDonations(): HasMany
    {
        return $this->donations()->where('status', 'completed');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function totalRaised(): float
    {
        return (float) $this->completedDonations()->sum('amount');
    }

    public function donorCount(): int
    {
        return $this->completedDonations()->count();
    }

    public function progressPercentage(): int
    {
        if ((float) $this->target_amount <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->totalRaised() / (float) $this->target_amount) * 100));
    }

    public function shareUrl(): string
    {
        return route('campaigns.show', $this->slug);
    }

    public function daysRemaining(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        $today = now()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();

        return $today->diffInDays($end, false);
    }

    /**
     * Generate unique slug from title.
     */
    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (self::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
