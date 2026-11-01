<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    use HasFactory;

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
        return asset('storage/' . $this->image_path);
    }

    public function getCategoryBadgeAttribute(): string
    {
        return match ($this->waste_category) {
            'organic' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'plastic' => 'bg-blue-100 text-blue-800 border-blue-200',
            'paper' => 'bg-amber-100 text-amber-800 border-amber-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
            'in_progress' => 'bg-sky-100 text-sky-800 border-sky-200',
            'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->waste_category) {
            'organic' => 'Sampah Organik',
            'plastic' => 'Sampah Plastik',
            'paper' => 'Sampah Kertas',
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
