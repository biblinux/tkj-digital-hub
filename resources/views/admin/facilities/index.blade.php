@extends('layouts.app')

@section('title', 'Kelola Fasilitas')

@section('content')
    <div class="btn-row" style="justify-content:space-between">
        <h1>Fasilitas Laboratorium</h1>
        <a class="btn" href="{{ route('admin.facilities.create') }}">+ Tambah fasilitas</a>
    </div>

    <section class="card">
        <p class="muted" style="margin-top:0">Total: {{ $items->total() }} fasilitas</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Foto</th><th>Urutan</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td><a href="{{ route('admin.facilities.edit', $item) }}">{{ $item->name }}</a></td>
                        <td>{{ $item->image_path ? 'Ada' : '-' }}</td>
                        <td>{{ $item->sort_order }}</td>
                        <td>
                            @if ($item->is_published)<span class="badge green">Tampil</span>
                            @else<span class="badge gray">Disembunyikan</span>@endif
                        </td>
                        <td><a class="btn small gray" href="{{ route('admin.facilities.edit', $item) }}">Ubah</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada fasilitas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $items->links() }}</div>
    </section>
@endsection
