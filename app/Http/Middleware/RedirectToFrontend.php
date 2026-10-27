<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jika FRONTEND_URL diisi, halaman publik Laravel dialihkan ke situs frontend
 * agar tidak ada dua situs yang tampil ganda. Rute login, dashboard, admin,
 * aktivasi, dan API tidak terpengaruh.
 */
class RedirectToFrontend
{
    public function handle(Request $request, Closure $next): Response
    {
        $frontend = config('tkj.frontend_url');

        if (! is_string($frontend) || $frontend === '' || ! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        // Alamat dasar server ini (api.jarvar.web.id) adalah pintu dashboard admin:
        // tamu diarahkan ke halaman login admin, yang sudah masuk ke dashboard.
        if ($request->path() === '/') {
            return redirect()->route($request->user() ? 'dashboard' : 'login');
        }

        // Alamat tujuan tetap dari konfigurasi, bukan dari input pengguna,
        // sehingga tidak bisa dipakai sebagai open redirect.
        return redirect()->away(rtrim($frontend, '/').'/', 302);
    }
}
