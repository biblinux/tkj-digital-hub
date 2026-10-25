@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <section class="hero">
        <h1>{{ config('tkj.name') }}</h1>
        <p>{{ config('tkj.tagline') }}. Temukan informasi jurusan, kirim saran, dan laporkan masalah perangkat atau jaringan sekolah.</p>
        <div class="btn-row">
            <a class="btn light" href="{{ route('pages.profile') }}">Kenali jurusan TKJ</a>
            @auth
                <a class="btn light" href="{{ route('reports.create') }}">Laporkan masalah IT</a>
            @else
                <a class="btn light" href="{{ route('login') }}">Masuk untuk lapor masalah IT</a>
            @endauth
        </div>
    </section>

    <section aria-labelledby="layanan">
        <h2 id="layanan" style="margin-top:0">Layanan</h2>
        <div class="grid cols-3">
            <article class="card accent">
                <h3>Informasi jurusan</h3>
                <p class="muted">Profil, bidang kompetensi, fasilitas laboratorium, dan galeri kegiatan.</p>
                <a href="{{ route('pages.competencies') }}">Lihat kompetensi</a>
            </article>
            <article class="card accent">
                <h3>Laporan masalah IT</h3>
                <p class="muted">Siswa yang sudah masuk dapat melaporkan masalah dan memantau status penanganannya.</p>
                <a href="{{ auth()->check() ? route('reports.create') : route('login') }}">Buat laporan</a>
            </article>
            <article class="card accent">
                <h3>Kotak saran</h3>
                <p class="muted">Sampaikan ide atau masukan untuk jurusan. Tidak perlu akun.</p>
                <a href="{{ route('suggestions.create') }}">Kirim saran</a>
            </article>
        </div>
    </section>

    <section aria-labelledby="lebih" style="margin-top:28px">
        <h2 id="lebih">Jelajahi lebih lanjut</h2>
        <div class="grid cols-4">
            <a class="card" href="{{ route('pages.organization') }}">Struktur kepengurusan</a>
            <a class="card" href="{{ route('pages.it-team') }}">Tim IT dan teknisi</a>
            <a class="card" href="{{ route('pages.topology') }}">Topologi jaringan</a>
            <a class="card" href="{{ route('pages.facilities') }}">Fasilitas laboratorium</a>
        </div>
    </section>
@endsection
