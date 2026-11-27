<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Event Ticket — Kisii TV Events</title>
</head>
<body style="margin:0;padding:0;background-color:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:28px 12px;background-color:#0f172a;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #1e293b;">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background:#0a1c3d;padding:24px 24px 20px;text-align:center;border-bottom:4px solid #10b981;">
                            @if($isTestPreview ?? false)
                                <div style="display:inline-block;background:#f59e0b;color:#0f172a;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:0.08em;padding:3px 10px;border-radius:999px;margin-bottom:10px;">
                                    Admin Test Preview Email
                                </div>
                            @endif
                            <div style="font-size:24px;font-weight:900;color:#ffffff;letter-spacing:-0.02em;">
                                KISII<span style="color:#d9252a;">TV</span> <span style="color:#34d399;">TICKETS</span>
                            </div>
                            <div style="font-size:12px;color:#bfdbfe;margin-top:5px;font-weight:600;letter-spacing:0.03em;">
                                {{ $templateHeading ?? 'Official Digital Ticket Confirmation' }}
                            </div>
                        </td>
                    </tr>

                    <!-- Event Cover Banner -->
                    @if($order->event?->coverImageUrl())
                        <tr>
                            <td style="padding:0;background:#020617;">
                                <img src="{{ $order->event->coverImageUrl() }}" alt="{{ $order->event->title }}"
                                    style="width:100%;max-height:210px;object-fit:cover;display:block;">
                            </td>
                        </tr>
                    @endif

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:28px 24px;">
                            <p style="margin:0 0 10px;font-size:15px;color:#0f172a;">
                                Hello <strong>{{ $order->buyer_name }}</strong>,
                            </p>
                            <p style="margin:0 0 22px;font-size:14px;line-height:1.65;color:#475569;">
                                {{ $templateIntro ?? 'Your ticket order is confirmed! Present your digital ticket pass below at the venue entrance for quick check-in.' }}
                            </p>

                            <!-- Event Summary Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:22px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#1b52bc;margin-bottom:8px;">
                                            Event Summary
                                        </div>
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:#64748b;">Event</td>
                                                <td style="padding:5px 0;font-size:13px;font-weight:800;color:#0f172a;text-align:right;">{{ $order->event->title }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:#64748b;">Date &amp; Time</td>
                                                <td style="padding:5px 0;font-size:13px;font-weight:700;color:#0f172a;text-align:right;">
                                                    {{ $order->event->starts_at?->timezone('Africa/Nairobi')->format('D, M j, Y · g:i A') }} EAT
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:#64748b;">Venue</td>
                                                <td style="padding:5px 0;font-size:13px;font-weight:700;color:#0f172a;text-align:right;">
                                                    {{ $order->event->venue }}{{ $order->event->city ? ', '.$order->event->city : '' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:#64748b;">Ticket Tier</td>
                                                <td style="padding:5px 0;font-size:13px;font-weight:700;color:#0f172a;text-align:right;">
                                                    {{ $order->ticketType->name }} (&times;{{ $order->quantity }})
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:#64748b;">Amount Paid</td>
                                                <td style="padding:5px 0;font-size:13px;font-weight:800;color:#059669;text-align:right;">
                                                    {{ (float) $order->total_amount > 0 ? $order->currency.' '.number_format((float) $order->total_amount, 0) : 'FREE' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px 0;font-size:12px;color:#64748b;">Order Reference</td>
                                                <td style="padding:5px 0;font-size:12px;font-family:monospace;font-weight:700;color:#334155;text-align:right;">
                                                    {{ $order->order_reference }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Digital Ticket Passes -->
                            <div style="margin-bottom:22px;">
                                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#059669;margin-bottom:10px;">
                                    Your Digital {{ $order->tickets->count() > 1 ? 'Ticket Passes' : 'Ticket Pass' }}
                                </div>

                                @foreach($order->tickets as $index => $ticket)
                                    @php
                                        $passUrl = ($isTestPreview ?? false) ? route('events.index') : $ticket->passUrl();
                                        $qrPayload = urlencode($ticket->ticket_code.' | '.$passUrl);
                                    @endphp
                                    <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a1c3d;color:#ffffff;border-radius:14px;margin-bottom:12px;overflow:hidden;">
                                        <tr>
                                            <td style="padding:18px 20px;">
                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td valign="middle">
                                                            <div style="font-size:10px;color:#6ee7b7;text-transform:uppercase;font-weight:800;letter-spacing:0.08em;">
                                                                Pass #{{ $index + 1 }} &bull; {{ $order->ticketType->name }}
                                                            </div>
                                                            <div style="font-size:20px;font-family:monospace;font-weight:900;letter-spacing:0.08em;color:#ffffff;margin:6px 0 12px;">
                                                                {{ $ticket->ticket_code }}
                                                            </div>
                                                            <a href="{{ $passUrl }}"
                                                               style="display:inline-block;background:#10b981;color:#022c22;text-decoration:none;font-size:12px;font-weight:900;padding:11px 18px;border-radius:10px;text-transform:uppercase;letter-spacing:0.04em;">
                                                                Open Digital Ticket Pass &rarr;
                                                            </a>
                                                        </td>
                                                        <td width="104" align="right" valign="middle" style="padding-left:12px;">
                                                            <div style="background:#ffffff;padding:6px;border-radius:10px;display:inline-block;">
                                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ $qrPayload }}"
                                                                    alt="QR {{ $ticket->ticket_code }}" width="86" height="86" style="display:block;">
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                @endforeach
                            </div>

                            <!-- Gate Instructions -->
                            <div style="background:#f1f5f9;border-left:4px solid #1b52bc;border-radius:8px;padding:12px 14px;font-size:12px;color:#334155;line-height:1.6;">
                                <strong style="color:#0f172a;">Gate Check-In Note:</strong>
                                {{ $templateInstructions ?? 'Keep this link private. Each ticket code is valid for a single check-in at the gate.' }}
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8fafc;padding:18px 24px;text-align:center;font-size:11px;color:#64748b;border-top:1px solid #e2e8f0;line-height:1.6;">
                            <div>{{ $templateFooter ?? 'Need help with your ticket? Reply to ticket@kisiitv.co.ke.' }}</div>
                            <div style="margin-top:6px;color:#94a3b8;">
                                &copy; {{ date('Y') }} Kisii TV Media Network &bull; Automated Ticketing Dispatch
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
