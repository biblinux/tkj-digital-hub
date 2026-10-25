<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItReport extends Model
{
    public const CATEGORIES = [
        'network' => 'Jaringan / internet',
        'hardware' => 'Perangkat keras',
        'software' => 'Perangkat lunak',
        'account' => 'Akun dan kata sandi',
        'lab' => 'Laboratorium',
        'other' => 'Lainnya',
    ];

    public const URGENCIES = [
        'low' => 'Rendah',
        'normal' => 'Normal',
        'high' => 'Tinggi',
        'urgent' => 'Mendesak',
    ];

    public const STATUSES = [
        'open' => 'Terbuka',
        'assigned' => 'Ditugaskan',
        'in_progress' => 'Sedang dikerjakan',
        'resolved' => 'Selesai ditangani',
        'closed' => 'Ditutup',
        'rejected' => 'Ditolak',
    ];

    /** Status yang dianggap sudah final. */
    public const FINAL_STATUSES = ['resolved', 'closed', 'rejected'];

    // user_id, status, assigned_to, dan urgensi hanya diatur lewat kode
    // yang sudah melalui otorisasi, bukan dari input pengguna langsung.
    protected $fillable = [
        'category',
        'title',
        'description',
        'location',
        'urgency',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ItReportUpdate::class)->orderBy('created_at')->orderBy('id');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function urgencyLabel(): string
    {
        return self::URGENCIES[$this->urgency] ?? $this->urgency;
    }
}
