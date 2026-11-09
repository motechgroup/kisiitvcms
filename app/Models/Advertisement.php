<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'advertiser_id',
        'placement_id',
        'title',
        'creative_type',
        'image_path',
        'html_code',
        'target_url',
        'starts_at',
        'ends_at',
        'is_active',
        'budget',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'budget' => 'decimal:2',
        ];
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class, 'placement_id');
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AdClick::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    public function recordImpression(?string $ip = null, ?string $userAgent = null): void
    {
        $this->impressions()->create([
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'viewed_at' => now(),
        ]);
    }

    public function recordClick(?string $ip = null, ?string $userAgent = null): void
    {
        $this->clicks()->create([
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'clicked_at' => now(),
        ]);
    }
}
