<?php

namespace App\Models;

use App\Models\Concerns\HasPublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proyek yang dibuat siswa sendiri. Baru tampil di situs publik setelah
 * disetujui admin (status published).
 */
class StudentProject extends Model
{
    use HasPublicImage;

    public const PENDING = 'pending';

    public const PUBLISHED = 'published';

    public const REJECTED = 'rejected';

    public const STATUSES = [
        self::PENDING => 'Menunggu',
        self::PUBLISHED => 'Tampil',
        self::REJECTED => 'Ditolak',
    ];

    public const MAX_PER_USER = 20;

    // status, reviewer, dan pemilik sengaja tidak mass-assignable.
    protected $fillable = ['title', 'description', 'tech', 'result', 'link_url'];

    protected function casts(): array
    {
        return ['tech' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', self::PUBLISHED);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
