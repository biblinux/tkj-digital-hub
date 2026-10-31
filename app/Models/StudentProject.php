<?php

namespace App\Models;

use App\Models\Concerns\HasPublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProject extends Model
{
    use HasPublicImage;

    public const STATUSES = [
        'pending' => 'Menunggu tinjauan',
        'published' => 'Terbit',
        'rejected' => 'Ditolak',
    ];

    // 'status' dan 'review_note' hanya diubah lewat kode yang terotorisasi.
    protected $fillable = ['title', 'description', 'technologies', 'result', 'link_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Daftar teknologi dari teks dipisah koma. */
    public function techList(): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', (string) $this->technologies)),
            fn (string $t) => $t !== ''
        ));
    }
}
