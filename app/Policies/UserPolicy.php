<?php

namespace App\Policies;

use App\Models\User;

/**
 * Aturan otorisasi manajemen pengguna.
 *
 * - Admin dengan izin manage_users mengelola siswa dan teknisi.
 * - Akun admin dan super admin hanya dapat dikelola super admin.
 * - Pengelolaan izin admin hanya oleh super admin.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('manage_users');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('manage_users');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->hasPermission('manage_users');
    }

    /**
     * Mengubah data akun lain (bukan diri sendiri).
     */
    public function update(User $actor, User $target): bool
    {
        if (! $actor->hasPermission('manage_users') || $actor->is($target)) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return in_array(
            $target->role,
            [User::ROLE_STUDENT, User::ROLE_TECHNICIAN],
            true
        );
    }

    /**
     * Peran yang boleh ditetapkan oleh pelaku.
     *
     * @return array<int, string>
     */
    public function assignableRoles(User $actor): array
    {
        if (! $actor->hasPermission('manage_users')) {
            return [];
        }

        $roles = [User::ROLE_STUDENT, User::ROLE_TECHNICIAN];

        // Hanya super admin yang boleh membuat/menetapkan admin.
        // Super admin sendiri dibuat lewat perintah artisan, bukan UI.
        if ($actor->isSuperAdmin()) {
            $roles[] = User::ROLE_ADMIN;
        }

        return $roles;
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && $target->is_active;
    }

    /**
     * Menghapus akun secara permanen. Akun super admin tidak dapat dihapus
     * lewat aplikasi; admin hanya dapat dihapus oleh super admin.
     */
    public function delete(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && ! $target->isSuperAdmin();
    }

    public function reactivate(User $actor, User $target): bool
    {
        // Hanya akun yang pernah diaktivasi; akun yang belum aktivasi
        // harus memakai tautan aktivasi.
        return $this->update($actor, $target)
            && ! $target->is_active
            && $target->activation()->whereNotNull('used_at')->exists();
    }

    public function resendActivation(User $actor, User $target): bool
    {
        return $this->update($actor, $target)
            && ! $target->is_active
            && ! $target->activation()->whereNotNull('used_at')->exists();
    }

    public function managePermissions(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin()
            && $actor->hasPermission('manage_users')
            && $target->isAdmin();
    }
}
