<?php

namespace App\Http\Controllers;

use App\Services\AccountActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    public function __construct(private AccountActivationService $activations)
    {
    }

    public function show(string $token): View
    {
        $activation = $this->activations->findValid($token);

        abort_unless(
            $activation && $activation->user && ! $activation->user->is_active,
            404,
            'Tautan aktivasi tidak valid atau sudah kedaluwarsa.'
        );

        return view('auth.activate', [
            'token' => $token,
            'email' => $activation->user->email,
            'ttl' => $this->activations->ttlMinutes(),
        ]);
    }

    public function activate(Request $request, string $token): RedirectResponse
    {
        $key = 'account-activation:'.hash('sha256', $token).'|'.$request->ip();

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

        if (! $this->activations->activate($token, $validated['password'])) {
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
