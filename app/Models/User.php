<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 'role' dan 'is_active' sengaja TIDAK mass-assignable: keduanya hanya
// boleh diubah lewat kode yang sudah melalui otorisasi (lihat UserController).
#[Fillable([
    'name',
    'username',
    'email',
    'display_name',
    'password',
    'photo_path',
    'two_factor_enabled',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_TECHNICIAN = 'technician';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /** Label peran untuk tampilan. */
    public const ROLES = [
        self::ROLE_STUDENT => 'Siswa',
        self::ROLE_TECHNICIAN => 'Teknisi',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_SUPER_ADMIN => 'Super Admin',
    ];

    /** Izin bawaan peran (tidak disimpan di database). */
    public const ROLE_PERMISSIONS = [
        self::ROLE_TECHNICIAN => ['handle_assigned_tickets'],
    ];

    /** Izin awal untuk admin baru (dapat diubah super admin). */
    public const ADMIN_DEFAULT_PERMISSIONS = [
        'manage_users',
        'manage_tickets',
        'manage_suggestions',
        'manage_site_content',
        'view_reports',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions'
        )->withTimestamps();
    }

    public function activation(): HasOne
    {
        return $this->hasOne(AccountActivation::class);
    }

    public function itReports(): HasMany
    {
        return $this->hasMany(ItReport::class);
    }

    public function assignedReports(): HasMany
    {
        return $this->hasMany(ItReport::class, 'assigned_to');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTechnician(): bool
    {
        return $this->role === self::ROLE_TECHNICIAN;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? (string) $this->role;
    }

    public function shownName(): string
    {
        return $this->display_name ?: $this->name;
    }

    /**
     * Memeriksa izin pengguna. Akun nonaktif tidak memiliki izin apa pun.
     */
    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Super admin memiliki seluruh izin.
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (in_array($permission, self::ROLE_PERMISSIONS[$this->role] ?? [], true)) {
            return true;
        }

        // Izin yang diberikan secara khusus hanya berlaku untuk admin.
        if (! $this->isAdmin()) {
            return false;
        }

        return $this->loadMissing('permissions')
            ->permissions
            ->contains('name', $permission);
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
