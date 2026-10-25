<?php

namespace App\Policies;

use App\Models\ItReport;
use App\Models\User;

/**
 * Aturan akses laporan masalah IT.
 *
 * - Siapa pun yang login boleh membuat laporan.
 * - Pelapor hanya melihat laporannya sendiri.
 * - Pengelola tiket (manage_tickets) melihat dan mengubah semua laporan.
 * - Teknisi hanya melihat dan mengubah laporan yang ditugaskan kepadanya.
 */
class ItReportPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function viewStaffList(User $user): bool
    {
        return $user->hasPermission('manage_tickets')
            || $user->hasPermission('handle_assigned_tickets');
    }

    public function view(User $user, ItReport $report): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $report->user_id === $user->id
            || $user->hasPermission('manage_tickets')
            || ($user->hasPermission('handle_assigned_tickets')
                && $report->assigned_to === $user->id);
    }

    /**
     * Mengubah status dan menambah catatan penanganan.
     */
    public function handle(User $user, ItReport $report): bool
    {
        return $user->hasPermission('manage_tickets')
            || ($user->hasPermission('handle_assigned_tickets')
                && $report->assigned_to === $user->id);
    }

    public function assign(User $user, ItReport $report): bool
    {
        return $user->hasPermission('manage_tickets');
    }

    /**
     * Melihat catatan internal.
     */
    public function viewInternalNotes(User $user, ItReport $report): bool
    {
        return $this->handle($user, $report);
    }
}
