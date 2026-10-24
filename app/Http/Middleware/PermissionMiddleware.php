<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        $user = $request->user();

        if (!$user || !$user->is_active) {
            abort(403, 'Akun tidak aktif atau tidak memiliki akses.');
        }

        if (!$user->hasPermission($permission)) {
            abort(403, 'Kamu tidak memiliki izin untuk melakukan tindakan ini.');
        }

        return $next($request);
    }
}
