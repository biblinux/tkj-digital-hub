@extends('layouts.app')

@section('title', 'Struktur Kepengurusan')

@section('content')
    <h1>Struktur Kepengurusan</h1>
    <p class="lead">Susunan pengurus jurusan TKJ.</p>

    @if (count(config('tkj.organization')))
        <div class="grid cols-3">
            @foreach (config('tkj.organization') as $member)
                <article class="card">
                    <h3>{{ $member['position'] ?? '' }}</h3>
                    <p style="margin:0">{{ $member['name'] ?? '' }}</p>
                </article>
            @endforeach
        </div>
    @else
        @include('partials.empty', ['title' => 'Struktur kepengurusan belum diisi', 'text' => 'Data kepengurusan resmi belum dimasukkan. Isi pada config/tkj.php bagian "organization".'])
    @endif
@endsection
