<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperAdmin extends Command
{
    protected $signature = 'tkj:create-super-admin
                            {--name= : Nama lengkap}
                            {--username= : Username}
                            {--email= : Alamat email}';

    protected $description = 'Membuat akun super admin pertama (kata sandi diminta secara interaktif).';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nama lengkap', required: true);
        $username = strtolower($this->option('username') ?: text('Username', required: true));
        $email = strtolower($this->option('email') ?: text('Email', required: true));

        // Kata sandi tidak diterima lewat opsi perintah agar tidak tersimpan di riwayat shell.
        $plain = password('Kata sandi (min. 12 karakter, huruf besar/kecil, angka, simbol)', required: true);

        $validator = Validator::make(
            compact('name', 'username', 'email') + ['password' => $plain],
            [
                'name' => ['required', 'string', 'max:100'],
                'username' => ['required', 'regex:/^[a-z0-9._-]{3,30}$/', Rule::unique('users', 'username')],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'display_name' => $name,
            'password' => $plain,
        ]);
        $user->role = User::ROLE_SUPER_ADMIN;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Super admin '{$username}' berhasil dibuat.");

        return self::SUCCESS;
    }
}
