@extends('layouts.app')

@section('title', 'Fasilitas Laboratorium')

@section('content')
    <h1>Fasilitas Laboratorium</h1>
    <p class="lead">Ruang dan perangkat yang mendukung kegiatan praktik.</p>

    @if (count(config('tkj.facilities')))
        <div class="grid cols-2">
            @foreach (config('tkj.facilities') as $facility)
                <article class="card accent">
                    <h3>{{ $facility['name'] ?? '' }}</h3>
                    <p class="muted" style="margin:0">{{ $facility['description'] ?? '' }}</p>
                </article>
            @endforeach
        </div>
    @else
        @include('partials.empty', ['title' => 'Data fasilitas belum diisi'])
    @endif
@endsection
