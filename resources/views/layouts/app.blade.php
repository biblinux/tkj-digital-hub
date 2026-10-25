<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('tkj.name')) - {{ config('tkj.name') }}</title>
    <meta name="description" content="@yield('description', config('tkj.tagline'))">
    <meta name="theme-color" content="#ea580c">
    <link rel="stylesheet" href="{{ asset('css/tkj.css') }}">
</head>
<body>
    <a class="skip-link" href="#konten">Lewati ke konten utama</a>

    <header class="site-header">
        <div class="container header-bar">
            <a class="brand" href="{{ route('home') }}">
                {{ config('tkj.name') }}
                <small>Teknik Komputer dan Jaringan</small>
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu-utama" id="nav-toggle">
                Menu
            </button>

            <nav class="main-nav" id="menu-utama" aria-label="Navigasi utama">
                <ul>
                    <li><a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>Beranda</a></li>
                    <li><a href="{{ route('pages.profile') }}" @if (request()->routeIs('pages.profile')) aria-current="page" @endif>Profil</a></li>
                    <li><a href="{{ route('pages.competencies') }}" @if (request()->routeIs('pages.competencies')) aria-current="page" @endif>Kompetensi</a></li>
                    <li><a href="{{ route('pages.facilities') }}" @if (request()->routeIs('pages.facilities')) aria-current="page" @endif>Fasilitas</a></li>
                    <li><a href="{{ route('pages.gallery') }}" @if (request()->routeIs('pages.gallery')) aria-current="page" @endif>Galeri</a></li>
                    <li><a href="{{ route('suggestions.create') }}" @if (request()->routeIs('suggestions.*')) aria-current="page" @endif>Saran</a></li>
                    <li><a href="{{ route('pages.contact') }}" @if (request()->routeIs('pages.contact')) aria-current="page" @endif>Kontak</a></li>

                    @auth
                        <li><a href="{{ route('dashboard') }}" class="cta" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="link">Keluar</button>
                            </form>
                        </li>
                    @else
                        <li><a href="{{ route('login') }}" class="cta">Masuk</a></li>
                    @endauth
                </ul>
            </nav>
        </div>
    </header>

    <main id="konten" tabindex="-1">
        <div class="container @yield('container_class')">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p style="margin:0 0 6px">
                &copy; {{ now()->format('Y') }} {{ config('tkj.name') }}
                @if (config('tkj.school.name')) &middot; {{ config('tkj.school.name') }} @endif
            </p>
            <p style="margin:0">
                <a href="{{ route('pages.organization') }}">Kepengurusan</a> &middot;
                <a href="{{ route('pages.it-team') }}">Tim IT</a> &middot;
                <a href="{{ route('pages.topology') }}">Topologi jaringan</a> &middot;
                <a href="{{ route('suggestions.create') }}">Kirim saran</a>
            </p>
        </div>
    </footer>

    <script>
        (function () {
            var btn = document.getElementById('nav-toggle');
            var nav = document.getElementById('menu-utama');
            if (!btn || !nav) return;
            btn.addEventListener('click', function () {
                var open = nav.classList.toggle('open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        })();
    </script>
</body>
</html>
