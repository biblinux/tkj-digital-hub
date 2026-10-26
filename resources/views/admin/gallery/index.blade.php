@extends('layouts.app')

@section('title', 'Kelola Galeri')

@section('content')
    <div class="btn-row" style="justify-content:space-between">
        <h1>Galeri Kegiatan</h1>
        <a class="btn" href="{{ route('admin.gallery.create') }}">+ Tambah foto</a>
    </div>

    <p class="muted">Total: {{ $items->total() }} foto</p>

    @if ($items->isEmpty())
        <div class="placeholder">Belum ada foto. Klik "Tambah foto" untuk mengunggah.</div>
    @else
        <div class="grid cols-4">
            @foreach ($items as $item)
                <div class="card" style="padding:12px">
                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->caption }}" loading="lazy"
                         style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;border:1px solid var(--line)">
                    <p style="margin:8px 0 4px"><strong>{{ $item->caption }}</strong></p>
                    <p class="muted" style="margin:0 0 8px">
                        Urutan {{ $item->sort_order }} ·
                        @if ($item->is_published)<span class="badge green">Tampil</span>
                        @else<span class="badge gray">Disembunyikan</span>@endif
                    </p>
                    <a class="btn small gray" href="{{ route('admin.gallery.edit', $item) }}">Ubah</a>
                </div>
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $items->links() }}</div>
    @endif
@endsection
