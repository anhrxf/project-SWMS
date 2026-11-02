<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi email Smart Waste Management')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Klik tombol di bawah untuk memverifikasi bahwa Anda memiliki akses ke alamat email ini.')
            ->action('Verifikasi email', $this->verificationUrl($notifiable))
            ->line('Tautan ini berlaku selama 60 menit. Jika Anda tidak membuat akun, abaikan email ini.');
    }
}
