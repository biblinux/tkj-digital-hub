<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    public const STATUSES = [
        'new' => 'Baru',
        'reviewed' => 'Sudah dibaca',
        'in_progress' => 'Sedang ditindaklanjuti',
        'done' => 'Selesai',
        'rejected' => 'Tidak dapat dilaksanakan',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
