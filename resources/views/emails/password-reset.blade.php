<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Kisii TV Password</title>
</head>
<body style="margin:0;padding:0;background-color:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:28px 12px;background-color:#0f172a;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #1e293b;">
                    <tr>
                        <td style="background:#0a1c3d;padding:24px;text-align:center;border-bottom:4px solid #d9252a;">
                            <div style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:-0.02em;">
                                KISII<span style="color:#d9252a;">TV</span>
                            </div>
                            <div style="font-size:12px;color:#bfdbfe;margin-top:4px;font-weight:600;">
                                Account Security &amp; Password Reset
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 24px;">
                            <p style="margin:0 0 12px;font-size:15px;color:#0f172a;">
                                Hello <strong>{{ $user->name ?? 'there' }}</strong>,
                            </p>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.65;color:#475569;">
                                We received a request to reset the password for your Kisii TV account (<strong>{{ $user->email }}</strong>). Click the button below to choose a new password.
                            </p>

                            <div style="text-align:center;margin:26px 0;">
                                <a href="{{ $resetUrl }}"
                                   style="display:inline-block;background:#d9252a;color:#ffffff;text-decoration:none;font-size:13px;font-weight:800;padding:13px 28px;border-radius:10px;text-transform:uppercase;letter-spacing:0.06em;">
                                    Reset Password &rarr;
                                </a>
                            </div>

                            <p style="margin:0 0 14px;font-size:12px;color:#64748b;line-height:1.6;">
                                This password reset link will expire in <strong>{{ $expireMinutes }} minutes</strong>. If you did not request a password reset, you can safely ignore this email.
                            </p>

                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:11px;color:#64748b;word-break:break-all;">
                                If the button doesn't work, copy and paste this link into your browser:<br>
                                <a href="{{ $resetUrl }}" style="color:#1b52bc;">{{ $resetUrl }}</a>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:16px 24px;text-align:center;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;">
                            Sent from hello@kisiitv.co.ke &bull; &copy; {{ date('Y') }} Kisii TV Media Network
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
