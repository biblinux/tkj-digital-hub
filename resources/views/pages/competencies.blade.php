@extends('layouts.app')

@section('title', 'Kompetensi')

@section('content')
    <h1>Kompetensi dan Bidang Keahlian</h1>
    <p class="lead">Gambaran umum bidang yang dipelajari di jurusan TKJ. Rincian resmi mengikuti kurikulum sekolah.</p>

    <div class="grid cols-2">
        @foreach (config('tkj.competencies') as $item)
            <article class="card accent">
                <h3>{{ $item['title'] }}</h3>
                <p class="muted" style="margin:0">{{ $item['description'] }}</p>
            </article>
        @endforeach
    </div>

    <h2>Kegiatan</h2>
    @if (count(config('tkj.activities')))
        <ul>
            @foreach (config('tkj.activities') as $activity)
                <li>{{ is_array($activity) ? ($activity['title'] ?? '') : $activity }}</li>
            @endforeach
        </ul>
    @else
        @include('partials.empty', ['title' => 'Daftar kegiatan belum diisi'])
    @endif
@endsection
