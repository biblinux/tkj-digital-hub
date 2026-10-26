@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>
    <p class="lead">Selamat datang, {{ $user->shownName() }}. <span class="badge">{{ $user->roleLabel() }}</span></p>

    {{-- Pengelolaan konten website --}}
    @if ($user->hasPermission('manage_site_content'))
        <h2>Kelola konten website</h2>
        <p class="muted" style="margin-top:0">Perubahan tampil di situs jurusan setelah status diterbitkan.</p>
        <div class="grid cols-4">
            <a class="card accent" href="{{ route('admin.news.index') }}" style="text-decoration:none"><h3>Berita</h3><span class="muted">Berita dan pengumuman</span></a>
            <a class="card accent" href="{{ route('admin.gallery.index') }}" style="text-decoration:none"><h3>Galeri</h3><span class="muted">Foto kegiatan</span></a>
            <a class="card accent" href="{{ route('admin.facilities.index') }}" style="text-decoration:none"><h3>Fasilitas</h3><span class="muted">Laboratorium dan ruang praktik</span></a>
            <a class="card accent" href="{{ route('admin.organization.index') }}" style="text-decoration:none"><h3>Struktur dan Tim</h3><span class="muted">Pengurus dan tim IT</span></a>
        </div>
    @endif

    {{-- Statistik pengelola --}}
    @isset($ticketStats)
        <h2>Ringkasan laporan IT</h2>
        <div class="grid cols-4">
            <div class="card stat"><div class="num">{{ $ticketStats['open'] }}</div><div class="label">Terbuka</div></div>
            <div class="card stat"><div class="num">{{ $ticketStats['unassigned'] }}</div><div class="label">Belum ditugaskan</div></div>
            <div class="card stat"><div class="num">{{ $ticketStats['in_progress'] }}</div><div class="label">Sedang ditangani</div></div>
            <div class="card stat"><div class="num">{{ $ticketStats['resolved'] }}</div><div class="label">Selesai</div></div>
        </div>
    @endisset

    @isset($userStats)
        <h2>Pengguna</h2>
        <div class="grid cols-4">
            <div class="card stat"><div class="num">{{ $userStats['total'] }}</div><div class="label">Total akun</div></div>
            <div class="card stat"><div class="num">{{ $userStats['active'] }}</div><div class="label">Aktif</div></div>
            <div class="card stat"><div class="num">{{ $userStats['pending'] }}</div><div class="label">Belum aktif / nonaktif</div></div>
        </div>
    @endisset

    @isset($newSuggestions)
        <p style="margin-top:16px"><a href="{{ route('admin.suggestions.index', ['status' => 'new']) }}">{{ $newSuggestions }} saran baru</a> menunggu ditinjau.</p>
    @endisset

    {{-- Tugas teknisi --}}
    @isset($assigned)
        <h2>Tugas untuk Anda</h2>
        @if ($assigned->isEmpty())
            <p class="muted">Tidak ada laporan yang sedang ditugaskan kepada Anda.</p>
        @else
            <div class="card table-wrap">
                <table>
                    <thead><tr><th>Nomor</th><th>Judul</th><th>Urgensi</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($assigned as $r)
                        <tr>
                            <td><a href="{{ route('reports.show', $r) }}">{{ $r->reference }}</a></td>
                            <td>{{ $r->title }}</td>
                            <td>{{ $r->urgencyLabel() }}</td>
                            <td><span class="badge blue">{{ $r->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endisset

    {{-- Laporan milik sendiri --}}
    <h2>Laporan saya</h2>
    <p class="muted">{{ $myOpenCount }} laporan masih berjalan.</p>
    @if ($myReports->isEmpty())
        <p class="muted">Anda belum membuat laporan.</p>
    @else
        <div class="card table-wrap">
            <table>
                <thead><tr><th>Nomor</th><th>Judul</th><th>Status</th><th>Dibuat</th></tr></thead>
                <tbody>
                @foreach ($myReports as $r)
                    <tr>
                        <td><a href="{{ route('reports.show', $r) }}">{{ $r->reference }}</a></td>
                        <td>{{ $r->title }}</td>
                        <td><span class="badge blue">{{ $r->statusLabel() }}</span></td>
                        <td>{{ $r->created_at->format('d-m-Y') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="btn-row" style="margin-top:20px">
        <a class="btn" href="{{ route('reports.create') }}">Buat laporan masalah IT</a>
        <a class="btn gray" href="{{ route('reports.index') }}">Semua laporan saya</a>
        <a class="btn gray" href="{{ route('suggestions.create') }}">Kirim saran</a>
    </div>

    {{-- Menu pengelolaan --}}
    @if ($user->can('viewStaffList', \App\Models\ItReport::class) || isset($newSuggestions) || isset($userStats))
        <h2>Pengelolaan</h2>
        <div class="btn-row">
            @can('viewStaffList', \App\Models\ItReport::class)
                <a class="btn" href="{{ route('admin.reports.index') }}">Kelola laporan IT</a>
            @endcan
            @isset($newSuggestions)
                <a class="btn" href="{{ route('admin.suggestions.index') }}">Kelola saran</a>
            @endisset
            @isset($userStats)
                <a class="btn" href="{{ route('admin.users.index') }}">Kelola pengguna</a>
            @endisset
        </div>
    @endif
@endsection
