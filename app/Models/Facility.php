<?php

namespace App\Models;

use App\Models\Concerns\HasPublicImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasPublicImage;

    protected $fillable = ['name', 'description', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
