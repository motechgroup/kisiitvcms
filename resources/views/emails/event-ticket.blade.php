<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Event Ticket — Kisii TV</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background:#0a1c3d;padding:24px;text-align:center;border-bottom:4px solid #d9252a;">
                            <div style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:-0.02em;">
                                KISII<span style="color:#d9252a;">TV</span> EVENTS
                            </div>
                            <div style="font-size:12px;color:#93c5fd;margin-top:4px;font-weight:600;">
                                Official Digital Ticket Confirmation
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 24px;">
                            <p style="margin:0 0 12px;font-size:15px;">Hello <strong>{{ $order->buyer_name }}</strong>,</p>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#475569;">
                                Your ticket order for <strong>{{ $order->event->title }}</strong> is confirmed! Present your digital ticket pass below at the venue entrance for quick check-in.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:20px;">
                                <tr>
                                    <td style="padding:4px 0;font-size:12px;color:#64748b;">Event</td>
                                    <td style="padding:4px 0;font-size:13px;font-weight:700;text-align:right;">{{ $order->event->title }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:12px;color:#64748b;">Date &amp; Time</td>
                                    <td style="padding:4px 0;font-size:13px;font-weight:700;text-align:right;">{{ $order->event->starts_at->timezone('Africa/Nairobi')->format('D, M j, Y · g:i A') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:12px;color:#64748b;">Venue</td>
                                    <td style="padding:4px 0;font-size:13px;font-weight:700;text-align:right;">{{ $order->event->venue }}{{ $order->event->city ? ', '.$order->event->city : '' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:12px;color:#64748b;">Ticket Tier</td>
                                    <td style="padding:4px 0;font-size:13px;font-weight:700;text-align:right;">{{ $order->ticketType->name }} (&times;{{ $order->quantity }})</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:12px;color:#64748b;">Order Reference</td>
                                    <td style="padding:4px 0;font-size:12px;font-family:monospace;font-weight:700;text-align:right;">{{ $order->order_reference }}</td>
                                </tr>
                            </table>

                            <div style="margin-bottom:20px;">
                                <div style="font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#1b52bc;margin-bottom:10px;">
                                    Your Digital Ticket {{ $order->tickets->count() > 1 ? 'Links' : 'Link' }}
                                </div>
                                @foreach($order->tickets as $index => $ticket)
                                    <div style="background:#0a1c3d;color:#ffffff;border-radius:10px;padding:14px 16px;margin-bottom:10px;">
                                        <div style="font-size:11px;color:#93c5fd;text-transform:uppercase;font-weight:700;">
                                            Pass #{{ $index + 1 }} &bull; {{ $order->ticketType->name }}
                                        </div>
                                        <div style="font-size:16px;font-family:monospace;font-weight:800;letter-spacing:0.06em;margin:4px 0 10px;">
                                            {{ $ticket->ticket_code }}
                                        </div>
                                        <a href="{{ $ticket->passUrl() }}"
                                           style="display:inline-block;background:#10b981;color:#ffffff;text-decoration:none;font-size:12px;font-weight:800;padding:10px 18px;border-radius:8px;text-transform:uppercase;letter-spacing:0.04em;">
                                            Open Digital Ticket Pass &rarr;
                                        </a>
                                    </div>
                                @endforeach
                            </div>

                            <p style="margin:0;font-size:12px;color:#64748b;line-height:1.5;">
                                Keep this link private. Each ticket code is valid for a single check-in at the gate.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:16px 24px;text-align:center;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;">
                            &copy; {{ date('Y') }} Kisii TV Media Network. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
