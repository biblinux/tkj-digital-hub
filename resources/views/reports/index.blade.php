@extends('layouts.app')

@section('title', 'Laporan Saya')

@section('content')
    <div class="btn-row" style="justify-content:space-between">
        <h1>Laporan Saya</h1>
        <a class="btn" href="{{ route('reports.create') }}">+ Buat laporan</a>
    </div>

    <div class="card table-wrap">
        <table>
            <thead><tr><th>Nomor</th><th>Judul</th><th>Kategori</th><th>Status</th><th>Dibuat</th></tr></thead>
            <tbody>
            @forelse ($reports as $r)
                <tr>
                    <td><a href="{{ route('reports.show', $r) }}">{{ $r->reference }}</a></td>
                    <td>{{ $r->title }}</td>
                    <td>{{ $r->categoryLabel() }}</td>
                    <td><span class="badge blue">{{ $r->statusLabel() }}</span></td>
                    <td>{{ $r->created_at->format('d-m-Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada laporan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $reports->links() }}</div>
@endsection
