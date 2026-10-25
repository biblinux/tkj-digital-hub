<?php

namespace App\Services;

use App\Mail\AccountActivationMail;
use App\Models\AccountActivation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountActivationService
{
    public function ttlMinutes(): int
    {
        return max(1, (int) config('tkj.activation_ttl_minutes', 60));
    }

    /**
     * Menerbitkan token aktivasi baru untuk pengguna.
     *
     * Hanya ada satu baris aktivasi per pengguna, sehingga token lama
     * otomatis tidak berlaku ketika token baru diterbitkan.
     * Token mentah hanya dikembalikan di sini; database menyimpan hash-nya.
     */
    public function issue(User $user): string
    {
        $rawToken = Str::random(64);

        AccountActivation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token_hash' => $this->hash($rawToken),
                'expires_at' => now()->addMinutes($this->ttlMinutes()),
                'used_at' => null,
            ]
        );

        return $rawToken;
    }

    /**
     * Mengirim email aktivasi. Mengembalikan false bila pengiriman gagal.
     * Pesan kesalahan dan token tidak dicatat ke log aplikasi.
     */
    public function send(User $user, string $rawToken): bool
    {
        try {
            Mail::to($user->email)->send(new AccountActivationMail(
                name: $user->name,
                activationUrl: $this->url($rawToken),
                ttlMinutes: $this->ttlMinutes(),
            ));
        } catch (\Throwable $exception) {
            Log::error('Pengiriman email aktivasi gagal.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Menerbitkan token baru lalu mengirimkannya.
     */
    public function issueAndSend(User $user): bool
    {
        return $this->send($user, $this->issue($user));
    }

    public function url(string $rawToken): string
    {
        return route('activation.show', ['token' => $rawToken]);
    }

    /**
     * Mencari aktivasi yang masih berlaku untuk token mentah tertentu.
     */
    public function findValid(string $rawToken): ?AccountActivation
    {
        return AccountActivation::query()
            ->with('user')
            ->where('token_hash', $this->hash($rawToken))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Mengaktifkan akun dan menetapkan kata sandi secara atomik.
     * Mengembalikan false bila token tidak valid, kedaluwarsa, atau terpakai.
     */
    public function activate(string $rawToken, string $password): bool
    {
        return DB::transaction(function () use ($rawToken, $password): bool {
            $activation = AccountActivation::query()
                ->where('token_hash', $this->hash($rawToken))
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $activation) {
                return false;
            }

            $user = User::query()->lockForUpdate()->find($activation->user_id);

            if (! $user || $user->is_active) {
                return false;
            }

            $user->password = $password; // di-hash otomatis oleh cast 'hashed'
            $user->is_active = true;
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->save();

            // Penanda pakai bersyarat: mencegah pemakaian ganda bila dua
            // permintaan berjalan hampir bersamaan.
            $marked = AccountActivation::query()
                ->whereKey($activation->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            return $marked === 1;
        });
    }

    private function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}
