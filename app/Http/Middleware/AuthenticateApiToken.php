<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi API lewat header "Authorization: Bearer <token>".
 * Menolak token tidak dikenal, kedaluwarsa, atau milik akun nonaktif.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! is_string($plain) || strlen($plain) !== 64 || ! ctype_alnum($plain)) {
            return $this->unauthenticated();
        }

        $token = ApiToken::query()
            ->with('user')
            ->where('token_hash', ApiToken::hashOf($plain))
            ->where('expires_at', '>', now())
            ->first();

        if (! $token || ! $token->user || ! $token->user->is_active) {
            return $this->unauthenticated();
        }

        // Perbarui penanda pemakaian paling sering sekali per menit.
        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    private function unauthenticated(): Response
    {
        return response()->json(['message' => 'Tidak terautentikasi.'], 401);
    }
}
