<?php

namespace App\Http\Controllers;

use App\Models\AccountActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    public function show(string $token): View
    {
        $activation = AccountActivation::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        abort_unless($activation, 404, 'Tautan aktivasi tidak valid atau sudah kedaluwarsa.');

        return view('auth.activate', [
            'token' => $token,
            'email' => $activation->user->email,
        ]);
    }

    public function activate(Request $request, string $token): RedirectResponse
    {
        $key = 'account-activation:' . hash('sha256', $token) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'password' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.',
            ]);
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $result = DB::transaction(function () use ($token, $validated) {
            $activation = AccountActivation::query()
                ->where('token_hash', hash('sha256', $token))
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $activation) {
                return false;
            }

            $user = $activation->user;

            if (! $user || $user->is_active) {
                return false;
            }

            $user->password = Hash::make($validated['password']);
            $user->is_active = true;
            $user->save();

            $activation->used_at = now();
            $activation->save();

            return true;
        });

        if (! $result) {
            RateLimiter::hit($key, 300);

            return redirect()
                ->route('login')
                ->withErrors([
                    'login' => 'Tautan aktivasi tidak valid, kedaluwarsa, atau sudah digunakan.',
                ]);
        }

        RateLimiter::clear($key);

        return redirect()
            ->route('login')
            ->with('success', 'Akun berhasil diaktifkan. Silakan login.');
    }
}
