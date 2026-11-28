<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StreamAccessPass extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_stream_id',
        'user_id',
        'payment_gateway_id',
        'gateway_code',
        'order_reference',
        'external_reference',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'amount',
        'currency',
        'status',
        'access_token',
        'device_fingerprint_hash',
        'active_session_id',
        'paid_at',
        'last_watched_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'last_watched_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_reference';
    }

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function isPaidAndActive(): bool
    {
        if ($this->status !== 'paid') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function matchesDeviceKey(?string $plainDeviceKey): bool
    {
        if (! $plainDeviceKey || ! $this->device_fingerprint_hash) {
            return false;
        }

        return hash_equals($this->device_fingerprint_hash, hash('sha256', $plainDeviceKey));
    }

    public static function generateOrderReference(): string
    {
        return 'KTV-SOD-'.strtoupper(Str::random(10));
    }

    public static function generateAccessToken(): string
    {
        return Str::random(48);
    }

    public static function deviceCookieName(int $streamId): string
    {
        return "kisiitv_sod_pass_{$streamId}";
    }
}
