<?php

namespace App\Models;

use App\Models\Concerns\HasPublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsPost extends Model
{
    use HasPublicImage;

    public const CATEGORIES = ['Kegiatan', 'Pengumuman', 'Prestasi', 'Informasi'];

    public const STATUSES = [
        'draft' => 'Draf (belum tayang)',
        'published' => 'Terbit',
    ];

    // created_by, updated_by, dan image_path diatur lewat kode yang sudah
    // melalui otorisasi, bukan dari input pengguna langsung.
    protected $fillable = ['title', 'category', 'summary', 'body', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'date'];
    }

    public function submitter(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** Hanya yang berstatus terbit dan tanggal tayangnya sudah tiba. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('review_status', 'approved')
            ->whereDate('published_at', '<=', now()->toDateString());
    }
}
