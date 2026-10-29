<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Teks profil jurusan. Akses dibatasi middleware permission:manage_site_content. */
class SiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.site.edit', ['values' => SiteSetting::allValues()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vision' => ['nullable', 'string', 'max:600'],
            'mission' => ['nullable', 'string', 'max:2000'],
            'about' => ['nullable', 'string', 'max:3000'],
            'competencies' => ['nullable', 'string', 'max:3000'],
            'address' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'map_url' => ['nullable', 'string', 'max:500', 'url:http,https'],
        ], [
            'email.email' => 'Format email tidak valid.',
            'map_url.url' => 'Tautan peta harus diawali http:// atau https://.',
        ]);

        // Batas jumlah baris supaya halaman publik tetap rapi.
        foreach (['mission' => 10, 'competencies' => 10] as $key => $max) {
            if (count(SiteSetting::lines($data[$key] ?? '')) > $max) {
                return back()->withInput()->withErrors([$key => "Maksimal {$max} baris."]);
            }
        }

        foreach (SiteSetting::KEYS as $key) {
            SiteSetting::put($key, $data[$key] ?? null);
        }

        return redirect()->route('admin.site.edit')->with('success', 'Profil jurusan disimpan.');
    }
}
