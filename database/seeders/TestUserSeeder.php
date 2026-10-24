<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'siswa.test@example.com'],
            [
                'name' => 'Siswa Pengujian',
                'username' => 'siswa.test',
                'display_name' => 'Siswa Pengujian',
                'password' => Hash::make('UjiLokal-2026!'),
                'role' => 'student',
                'is_active' => true,
            ]
        );
    }
}
