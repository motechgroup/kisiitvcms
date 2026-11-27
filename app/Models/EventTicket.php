<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EventTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_ticket_order_id',
        'event_id',
        'event_ticket_type_id',
        'ticket_code',
        'access_token',
        'attendee_name',
        'attendee_email',
        'attendee_phone',
        'status',
        'checked_in_at',
        'checked_in_by',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'access_token';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(EventTicketOrder::class, 'event_ticket_order_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(EventTicketType::class, 'event_ticket_type_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function passUrl(): string
    {
        return route('tickets.show', $this->access_token);
    }

    public static function generateTicketCode(): string
    {
        do {
            $code = 'KTV-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (self::where('ticket_code', $code)->exists());

        return $code;
    }

    public static function generateAccessToken(): string
    {
        return Str::random(48);
    }
}
