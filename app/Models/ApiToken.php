<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Token akses API untuk frontend statis. Token mentah hanya ditampilkan
 * satu kali saat dibuat; basis data menyimpan hash-nya.
 */
class ApiToken extends Model
{
    protected $fillable = ['name'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashOf(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Membuat token baru. Mengembalikan [model, token mentah].
     *
     * @return array{0: self, 1: string}
     */
    public static function issueFor(User $user, string $name = 'frontend'): array
    {
        $plain = Str::random(64);

        $token = new self(['name' => $name]);
        $token->user_id = $user->id;
        $token->token_hash = self::hashOf($plain);
        $token->expires_at = now()->addMinutes((int) config('tkj.api_token_ttl_minutes', 10080));
        $token->save();

        return [$token, $plain];
    }
}
