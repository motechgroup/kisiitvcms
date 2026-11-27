<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KisiiTvResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $mailer = (string) config('mail.auth_mailer', 'auth_smtp');
        $fromAddress = (string) config('mail.auth_from.address', 'hello@kisiitv.co.ke');
        $fromName = (string) config('mail.auth_from.name', 'Kisii TV Accounts');

        return (new MailMessage)
            ->mailer($mailer)
            ->from($fromAddress, $fromName)
            ->subject('Reset Your Kisii TV Account Password')
            ->view('emails.password-reset', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl,
                'expireMinutes' => $expireMinutes,
            ]);
    }
}
