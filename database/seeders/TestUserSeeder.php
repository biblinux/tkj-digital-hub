<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun contoh untuk pengembangan lokal saja.
 * Seeder ini menolak berjalan di environment produksi.
 */
class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('TestUserSeeder dilewati: hanya untuk environment local/testing.');

            return;
        }

        $user = User::firstOrNew(['email' => 'siswa.test@example.com']);

        if (! $user->exists) {
            $user->fill([
                'name' => 'Siswa Pengujian',
                'username' => 'siswa.test',
                'display_name' => 'Siswa Pengujian',
                'password' => Hash::make('UjiLokal-2026!'),
            ]);
            $user->role = User::ROLE_STUDENT;
            $user->is_active = true;
            $user->save();
        }
    }
}
