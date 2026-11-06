<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class Report extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'waste_category',
        'ai_detected_category',
        'ai_confidence',
        'image_path',
        'latitude',
        'longitude',
        'location_address',
        'status',
        'admin_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'ai_confidence' => 'float',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReportStatusLog::class)->latest();
    }

    public function getImageUrlAttribute(): string
    {
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        if (! $this->image_path || ! $this->exists) {
            return '';
        }

        return URL::temporarySignedRoute(
            'reports.image',
            now()->addMinutes(30),
            ['report' => $this->getKey()],
        );
    }

    public function getCategoryBadgeAttribute(): string
    {
        return match ($this->waste_category) {
            'organic' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'plastic' => 'bg-blue-50 text-blue-700 border-blue-100',
            'paper' => 'bg-amber-50 text-amber-700 border-amber-100',
            'other' => 'bg-purple-50 text-purple-700 border-purple-100',
            default => 'bg-slate-50 text-slate-700 border-slate-100',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
            'in_progress' => 'bg-sky-50 text-sky-700 border-sky-100',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'rejected' => 'bg-rose-50 text-rose-700 border-rose-100',
            default => 'bg-slate-50 text-slate-700 border-slate-100',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->waste_category) {
            'organic' => 'Sampah Organik',
            'plastic' => 'Sampah Plastik',
            'paper' => 'Sampah Kertas',
            'other' => 'Sampah Campuran',
            default => 'Sampah Lainnya',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu (Pending)',
            'in_progress' => 'Diproses (In Progress)',
            'completed' => 'Selesai (Completed)',
            'rejected' => 'Ditolak (Rejected)',
            default => ucfirst($this->status),
        };
    }
}
