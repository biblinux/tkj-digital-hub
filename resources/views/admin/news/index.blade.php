@extends('layouts.app')

@section('title', 'Kelola Berita')

@section('content')
    <div class="btn-row" style="justify-content:space-between">
        <h1>Berita dan Pengumuman</h1>
        <a class="btn" href="{{ route('admin.news.create') }}">+ Tambah berita</a>
    </div>

    <form method="GET" class="filters card">
        <div style="flex:2 1 240px">
            <label for="q">Cari judul</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="100">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                @foreach (\App\Models\NewsPost::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><button class="btn" type="submit">Terapkan</button></div>
    </form>

    <section class="card">
        <p class="muted" style="margin-top:0">Total: {{ $items->total() }} berita</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Tanggal tayang</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td><a href="{{ route('admin.news.edit', $item) }}">{{ $item->title }}</a></td>
                        <td>{{ $item->category }}</td>
                        <td>
                            @if ($item->status === 'published')<span class="badge green">Terbit</span>
                            @else<span class="badge gray">Draf</span>@endif
                        </td>
                        <td>{{ $item->published_at?->format('d-m-Y') ?? '-' }}</td>
                        <td><a class="btn small gray" href="{{ route('admin.news.edit', $item) }}">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada berita.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $items->links() }}</div>
    </section>
@endsection
