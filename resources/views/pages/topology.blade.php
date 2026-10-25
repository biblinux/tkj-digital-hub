@extends('layouts.app')

@section('title', 'Topologi Jaringan')

@section('content')
    <h1>Dokumentasi Topologi Jaringan</h1>
    <p class="lead">Diagram dan keterangan tata letak jaringan laboratorium.</p>

    @php($topology = config('tkj.network_topology'))

    @if (! empty($topology['image']))
        <figure class="card" style="margin:0">
            <img src="{{ asset($topology['image']) }}" alt="Diagram topologi jaringan" style="max-width:100%;height:auto">
            @if (! empty($topology['description']))
                <figcaption class="muted" style="margin-top:10px">{{ $topology['description'] }}</figcaption>
            @endif
        </figure>
    @else
        @include('partials.empty', ['title' => 'Diagram topologi belum diunggah', 'text' => 'Letakkan gambar di folder public/images lalu isi network_topology pada config/tkj.php.'])
    @endif
@endsection
