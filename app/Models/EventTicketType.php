<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'quantity_available',
        'quantity_sold',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity_available' => 'integer',
            'quantity_sold' => 'integer',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(EventTicketOrder::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    public function remaining(): ?int
    {
        if ($this->quantity_available === null) {
            return null;
        }

        return max(0, $this->quantity_available - $this->quantity_sold);
    }

    public function isSoldOut(): bool
    {
        $remaining = $this->remaining();

        return $remaining !== null && $remaining <= 0;
    }

    public function hasCapacityFor(int $quantity): bool
    {
        $remaining = $this->remaining();

        return $remaining === null || $remaining >= $quantity;
    }
}
