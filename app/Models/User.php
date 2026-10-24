<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'username',
    'email',
    'display_name',
    'password',
    'role',
    'photo_path',
    'is_active',
    'two_factor_enabled',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Relasi izin yang dimiliki pengguna.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions'
        )->withTimestamps();
    }

    /**
     * Memeriksa apakah pengguna memiliki izin tertentu.
     */
    public function hasPermission(string $permission): bool
    {
        // Super Admin memiliki seluruh izin.
        if ($this->role === 'super_admin') {
            return true;
        }

        // Hanya admin tambahan yang dapat menggunakan
        // izin yang diberikan secara khusus.
        if ($this->role !== 'admin') {
            return false;
        }

        return $this->permissions()
            ->where('permissions.name', $permission)
            ->exists();
    }

    /**
     * Konversi tipe atribut model.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }
}
