<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'manage_users',
                'description' => 'Mengelola akun pengguna',
            ],
            [
                'name' => 'manage_admins',
                'description' => 'Mengelola akun dan hak akses admin',
            ],
            [
                'name' => 'manage_tickets',
                'description' => 'Mengelola seluruh tiket IT',
            ],
            [
                'name' => 'handle_assigned_tickets',
                'description' => 'Menangani tiket yang ditugaskan',
            ],
            [
                'name' => 'manage_inventory',
                'description' => 'Mengelola inventaris perangkat IT',
            ],
            [
                'name' => 'manage_students',
                'description' => 'Mengelola data siswa',
            ],
            [
                'name' => 'manage_site_content',
                'description' => 'Mengelola konten website',
            ],
            [
                'name' => 'view_reports',
                'description' => 'Melihat laporan dan statistik',
            ],
            [
                'name' => 'manage_settings',
                'description' => 'Mengelola pengaturan sistem',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }
    }
}
