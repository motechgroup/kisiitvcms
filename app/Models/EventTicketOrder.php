<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EventTicketOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_ticket_type_id',
        'user_id',
        'payment_gateway_id',
        'gateway_code',
        'order_reference',
        'external_reference',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'quantity',
        'unit_price',
        'total_amount',
        'currency',
        'status',
        'paid_at',
        'email_sent_at',
        'sms_sent_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'email_sent_at' => 'datetime',
            'sms_sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_reference';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(EventTicketType::class, 'event_ticket_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    public static function generateReference(): string
    {
        return 'KTV-TKT-'.strtoupper(Str::random(10));
    }
}
