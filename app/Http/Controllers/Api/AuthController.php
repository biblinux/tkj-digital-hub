<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $login = Str::lower(trim($data['login']));
        $key = 'api-login:'.hash('sha256', $login.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan login. Coba lagi nanti.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Pesan yang sama untuk kata sandi salah dan akun belum aktif,
        // agar status akun tidak bocor.
        if (! Auth::validate([$field => $login, 'password' => $data['password'], 'is_active' => true])) {
            RateLimiter::hit($key, 60);

            return response()->json(['message' => 'Kredensial tidak valid atau akun belum aktif.'], 422);
        }

        /** @var User $user */
        $user = User::query()->where($field, $login)->firstOrFail();

        // Akun admin hanya dipakai di dashboard (api.jarvar.web.id), bukan di situs publik.
        if (! $user->canUseFrontendLogin()) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'message' => 'Akun admin masuk lewat dashboard di api.jarvar.web.id, bukan di situs ini.',
            ], 403);
        }

        RateLimiter::clear($key);

        [$token, $plain] = ApiToken::issueFor($user, $data['device_name'] ?? 'frontend');

        return response()->json([
            'token' => $plain,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at->toIso8601String(),
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json(['message' => 'Anda telah keluar.']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->shownName(),
            'username' => $user->username,
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
            'photo_url' => $user->photoUrl(),
        ];
    }
}
