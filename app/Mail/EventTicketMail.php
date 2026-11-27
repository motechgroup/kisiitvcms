<?php

namespace App\Mail;

use App\Models\EventTicketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventTicketOrder $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Ticket for {$this->order->event->title} — Kisii TV Events",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-ticket',
        );
    }
}
