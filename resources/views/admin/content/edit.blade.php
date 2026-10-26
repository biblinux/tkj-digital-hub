@extends('layouts.app')

@section('title', $heading)
@section('container_class', 'narrow')

@section('content')
    <h1>{{ $heading }}</h1>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card">
        @csrf
        @method('PUT')
        @include($form, ['item' => $item])
        <div class="btn-row" style="margin-top:20px">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn gray" href="{{ $back }}">Batal</a>
        </div>
    </form>

    @if ($delete)
        <form method="POST" action="{{ $delete }}" class="card" style="margin-top:20px"
              onsubmit="return confirm('Hapus data ini? Tindakan ini tidak dapat dibatalkan.')">
            @csrf
            @method('DELETE')
            <h2 style="margin-top:0">Hapus</h2>
            <p class="muted">Data dan fotonya dihapus permanen dan hilang dari situs publik.</p>
            <button class="btn danger" type="submit">Hapus</button>
        </form>
    @endif
@endsection
