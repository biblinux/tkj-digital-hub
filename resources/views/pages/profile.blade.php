@extends('layouts.app')

@section('title', 'Profil Jurusan TKJ')

@section('content')
    <h1>Profil Jurusan TKJ</h1>
    <p class="lead">Teknik Komputer dan Jaringan membekali peserta didik dengan kemampuan merancang, memasang, mengelola, dan merawat sistem komputer serta jaringan.</p>

    <h2>Tentang sekolah</h2>
    @if (config('tkj.school.name'))
        <div class="card">
            <dl class="details">
                <dt>Nama sekolah</dt><dd>{{ config('tkj.school.name') }}</dd>
                @if (config('tkj.school.address'))<dt>Alamat</dt><dd>{{ config('tkj.school.address') }}</dd>@endif
            </dl>
        </div>
    @else
        @include('partials.empty', ['title' => 'Identitas sekolah belum diisi', 'text' => 'Isi SCHOOL_NAME dan SCHOOL_ADDRESS pada file .env untuk menampilkan data sekolah.'])
    @endif

    <h2>Kegiatan jurusan</h2>
    @if (count(config('tkj.activities')))
        <ul>
            @foreach (config('tkj.activities') as $activity)
                <li>{{ $activity['title'] ?? $activity }}</li>
            @endforeach
        </ul>
    @else
        @include('partials.empty', ['title' => 'Daftar kegiatan belum diisi'])
    @endif
@endsection
