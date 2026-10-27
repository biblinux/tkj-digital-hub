<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $login = Str::lower(trim($request->input('login')));
        $key = 'login:'.hash('sha256', $login.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Terlalu banyak percobaan login. Coba lagi dalam '
                    .RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // is_active = true memastikan akun yang belum aktif / dinonaktifkan
        // ditolak dengan pesan yang sama seperti kata sandi salah, sehingga
        // status akun tidak bocor ke pihak luar.
        $credentials = [
            $field => $login,
            'password' => $request->input('password'),
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'login' => 'Kredensial tidak valid atau akun belum aktif.',
            ]);
        }

        // Dashboard hanya untuk staf. Akun siswa masuk lewat situs publik.
        if (! $request->user()->canUseDashboardLogin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'login' => 'Halaman ini khusus admin dan teknisi. Akun siswa masuk lewat situs utama jarvar.web.id.',
            ]);
        }

        RateLimiter::clear($key);

        // Cegah session fixation.
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
