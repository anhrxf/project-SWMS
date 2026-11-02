<?php

namespace App\Notifications;

use DateTimeInterface;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    public function __construct(
        private readonly DateTimeInterface $expiresAt,
    ) {}

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi email Smart Waste Management')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Klik tombol di bawah untuk memverifikasi bahwa Anda memiliki akses ke alamat email ini.')
            ->action('Verifikasi email', $this->verificationUrl($notifiable))
            ->line('Tautan ini berlaku selama 5 menit. Jika Anda tidak membuat akun, abaikan email ini.');
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            $this->expiresAt,
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
