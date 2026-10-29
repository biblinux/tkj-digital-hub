<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('tkj.name')) - {{ config('tkj.name') }}</title>
    <meta name="description" content="@yield('description', config('tkj.tagline'))">
    <meta name="theme-color" content="#C84F08">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap">
    <script>document.documentElement.classList.add('js')</script>
    <link rel="stylesheet" href="{{ asset('css/tkj.css') }}?v=4">
</head>
@php
    $frontend = config('tkj.frontend_url');
    $user = auth()->user();
    $can = fn (string $permission) => $user && $user->hasPermission($permission);

    // Menu pengelolaan: hanya yang boleh diakses akun ini. Server tetap memeriksa
    // izin pada setiap rute; menu ini hanya merapikan tampilan.
    $adminLinks = [];
    if ($user) {
        $adminLinks[] = ['Dashboard', route('dashboard'), request()->routeIs('dashboard')];
        if ($can('manage_site_content')) {
            $adminLinks[] = ['Berita', route('admin.news.index'), request()->routeIs('admin.news.*')];
            $adminLinks[] = ['Galeri', route('admin.gallery.index'), request()->routeIs('admin.gallery.*')];
            $adminLinks[] = ['Fasilitas', route('admin.facilities.index'), request()->routeIs('admin.facilities.*')];
            $adminLinks[] = ['Struktur dan Tim', route('admin.organization.index'), request()->routeIs('admin.organization.*')];
            $pendingProjects = \App\Models\StudentProject::where('status', 'pending')->count();
            $adminLinks[] = ['Proyek Siswa'.($pendingProjects ? " ({$pendingProjects})" : ''), route('admin.projects.index'), request()->routeIs('admin.projects.*')];
        }
        if ($can('manage_suggestions')) {
            $adminLinks[] = ['Saran', route('admin.suggestions.index'), request()->routeIs('admin.suggestions.*')];
        }
        if (\Illuminate\Support\Facades\Gate::allows('viewStaffList', \App\Models\ItReport::class)) {
            $adminLinks[] = ['Laporan IT', route('admin.reports.index'), request()->routeIs('admin.reports.*', 'reports.*')];
        } else {
            $adminLinks[] = ['Laporan saya', route('reports.index'), request()->routeIs('reports.*')];
        }
        if ($can('manage_users')) {
            $adminLinks[] = ['Pengguna', route('admin.users.index'), request()->routeIs('admin.users.*')];
        }
    }
@endphp
<body>
    <a class="skip-link" href="#konten">Lewati ke konten utama</a>

    <header class="site-header">
        <div class="container header-bar">
            <a class="brand" href="{{ $user ? route('dashboard') : ($frontend ?: route('home')) }}">
                <span class="brand-mark" aria-hidden="true">TKJ</span>
                <span class="brand-text">
                    <strong>{{ config('tkj.name') }}</strong>
                    <small>{{ config('tkj.school.name') ?: 'Teknik Komputer dan Jaringan' }}</small>
                </span>
            </a>

            <nav class="header-actions" aria-label="Navigasi utama">
                @if ($frontend)
                    <a class="plain site-link" href="{{ $frontend }}" rel="noopener">Lihat situs</a>
                @else
                    <a class="plain" href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>Beranda</a>
                    <a class="plain" href="{{ route('pages.profile') }}" @if (request()->routeIs('pages.profile')) aria-current="page" @endif>Profil</a>
                    <a class="plain" href="{{ route('pages.facilities') }}" @if (request()->routeIs('pages.facilities')) aria-current="page" @endif>Fasilitas</a>
                    <a class="plain" href="{{ route('pages.gallery') }}" @if (request()->routeIs('pages.gallery')) aria-current="page" @endif>Galeri</a>
                    <a class="plain" href="{{ route('suggestions.create') }}" @if (request()->routeIs('suggestions.*')) aria-current="page" @endif>Saran</a>
                    <a class="plain" href="{{ route('pages.contact') }}" @if (request()->routeIs('pages.contact')) aria-current="page" @endif>Kontak</a>
                @endif

                @auth
                    <a class="plain who" href="{{ route('account.edit') }}" @if (request()->routeIs('account.*')) aria-current="page" @endif>{{ $user->shownName() }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="link">Keluar</button>
                    </form>
                @else
                    <a class="btn small" href="{{ route('login') }}">Masuk</a>
                @endauth
            </nav>
        </div>

        @if ($adminLinks)
            @php($currentLabel = collect($adminLinks)->first(fn ($l) => $l[2])[0] ?? 'Menu')
            <nav class="admin-nav" aria-label="Menu pengelolaan">
                <div class="container menu-bar">
                    <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="admin-menu">
                        <span>Menu: <strong>{{ $currentLabel }}</strong></span>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>
                <ul class="container" id="admin-menu">
                    @foreach ($adminLinks as [$label, $href, $active])
                        <li><a href="{{ $href }}" @if ($active) aria-current="page" @endif>{{ $label }}</a></li>
                    @endforeach
                    @if ($frontend)
                        <li class="menu-extra"><a href="{{ $frontend }}" rel="noopener">Lihat situs</a></li>
                    @endif
                </ul>
            </nav>
        @endif
    </header>

    <main id="konten" tabindex="-1">
        <div class="container @yield('container_class')">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p class="foot-brand">{{ config('tkj.name') }}</p>
            <p class="foot-line">
                &copy; {{ now()->format('Y') }} Teknik Komputer dan Jaringan
                @if (config('tkj.school.name')) &middot; {{ config('tkj.school.name') }} @endif
            </p>
            @if ($frontend)
                <p class="foot-line"><a href="{{ $frontend }}" rel="noopener">Kembali ke situs jurusan</a></p>
            @else
                <p class="foot-line">
                    <a href="{{ route('pages.organization') }}">Kepengurusan</a> &middot;
                    <a href="{{ route('pages.it-team') }}">Tim IT</a> &middot;
                    <a href="{{ route('pages.topology') }}">Topologi jaringan</a> &middot;
                    <a href="{{ route('suggestions.create') }}">Kirim saran</a>
                </p>
            @endif
        </div>
    </footer>

    <script>
        (function () {
            var toggle = document.querySelector('.menu-toggle'), menu = document.getElementById('admin-menu');
            if (toggle && menu) {
                toggle.addEventListener('click', function () {
                    var open = toggle.getAttribute('aria-expanded') !== 'true';
                    toggle.setAttribute('aria-expanded', String(open));
                    menu.classList.toggle('is-open', open);
                });
            }
            // Tabel menjadi kartu di layar kecil: setiap sel diberi label dari judul kolomnya.
            document.querySelectorAll('.table-wrap table').forEach(function (table) {
                var heads = [].map.call(table.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    [].forEach.call(row.children, function (cell, i) {
                        if (heads[i] && !cell.hasAttribute('colspan')) { cell.setAttribute('data-label', heads[i]); }
                    });
                });
                table.classList.add('stack');
            });
        })();
    </script>
</body>
</html>
