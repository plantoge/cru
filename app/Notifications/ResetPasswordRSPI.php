<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordRSPI extends Notification
{
    public function __construct(protected string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Token sudah divalidasi & disimpan (hashed) oleh broker Password bawaan
        // Laravel di tabel password_reset_tokens — beda dari verifikasi email yang
        // pakai signed URL manual, di sini tak perlu bikin signature sendiri.
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset Password Akun eProposal RSPI')
            ->markdown('emails.reset-password', [
                'url' => $url,
                'user' => $notifiable,
            ]);
    }
}
