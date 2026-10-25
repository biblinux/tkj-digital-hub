<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed aman untuk database yang sudah berisi data (idempotent).
     * Akun super admin pertama dibuat dengan: php artisan tkj:create-super-admin
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        if (app()->environment('local')) {
            $this->call(TestUserSeeder::class);
        }
    }
}
