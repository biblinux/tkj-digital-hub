<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItReportUpdate extends Model
{
    protected $fillable = [
        'it_report_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ItReport::class, 'it_report_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
