@extends('layouts.app')

@section('title', 'Tim IT dan Teknisi')

@section('content')
    <h1>Tim IT dan Teknisi</h1>
    <p class="lead">Tim yang menangani laporan masalah perangkat dan jaringan.</p>

    @if (count(config('tkj.it_team')))
        <div class="grid cols-3">
            @foreach (config('tkj.it_team') as $member)
                <article class="card">
                    <h3>{{ $member['name'] ?? '' }}</h3>
                    <p class="muted" style="margin:0">{{ $member['role'] ?? '' }}</p>
                </article>
            @endforeach
        </div>
    @else
        @include('partials.empty', ['title' => 'Daftar tim IT belum diisi'])
    @endif

    <p style="margin-top:20px">Butuh bantuan teknis? <a href="{{ auth()->check() ? route('reports.create') : route('login') }}">Buat laporan masalah IT</a>.</p>
@endsection
