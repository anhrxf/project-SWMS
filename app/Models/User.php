<?php

namespace App\Models;

use Database\Factories\UserFactory;
use App\Notifications\VerifyEmailNotification;
use DateTimeInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->sendVerificationNotificationUntil(
            now()->addMinutes(config('auth.verification.expire', 5))
        );
    }

    public function sendVerificationNotificationUntil(DateTimeInterface $expiresAt): void
    {
        $this->notify(new VerifyEmailNotification($expiresAt));
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReportStatusLog::class);
    }
}
