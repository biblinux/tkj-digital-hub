@extends('layouts.app')

@section('title', 'Galeri Kegiatan')

@section('content')
    <h1>Galeri Kegiatan</h1>
    <p class="lead">Dokumentasi kegiatan jurusan TKJ.</p>

    @if (count(config('tkj.gallery')))
        <div class="grid cols-3">
            @foreach (config('tkj.gallery') as $photo)
                <figure class="card" style="margin:0">
                    <img src="{{ asset($photo['image']) }}" alt="{{ $photo['caption'] ?? 'Foto kegiatan' }}" loading="lazy" style="max-width:100%;height:auto;border-radius:6px">
                    @if (! empty($photo['caption']))
                        <figcaption class="muted" style="margin-top:8px">{{ $photo['caption'] }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    @else
        @include('partials.empty', ['title' => 'Galeri belum berisi foto', 'text' => 'Foto kegiatan akan tampil di sini setelah ditambahkan pada config/tkj.php.'])
    @endif
@endsection
