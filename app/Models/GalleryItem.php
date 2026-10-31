<?php

namespace App\Models;

use App\Models\Concerns\HasPublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    use HasPublicImage;

    protected $fillable = ['caption', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('review_status', 'approved');
    }

    public function submitter(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
