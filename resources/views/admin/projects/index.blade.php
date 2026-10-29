@extends('layouts.app')

@section('title', 'Proyek Siswa')

@section('content')
    <h1>Proyek Siswa</h1>
    <p class="lead">Proyek yang dibuat siswa tampil di situs hanya setelah Anda menyetujuinya.</p>

    <div class="btn-row" style="margin-bottom:16px">
        @foreach (['pending' => 'Menunggu', 'published' => 'Tampil', 'rejected' => 'Ditolak', 'all' => 'Semua'] as $key => $label)
            <a class="btn small {{ $status === $key ? '' : 'gray' }}" href="{{ route('admin.projects.index', ['status' => $key]) }}">
                {{ $label }}@if ($key !== 'all') ({{ $counts[$key] ?? 0 }})@endif
            </a>
        @endforeach
    </div>

    @if ($projects->isEmpty())
        <div class="placeholder">Tidak ada proyek pada daftar ini.</div>
    @else
        <div class="card table-wrap">
            <table>
                <thead><tr><th>Judul</th><th>Pemilik</th><th>Status</th><th>Dikirim</th><th></th></tr></thead>
                <tbody>
                    @foreach ($projects as $p)
                        <tr>
                            <td><a href="{{ route('admin.projects.show', $p) }}">{{ $p->title }}</a></td>
                            <td>{{ $p->user?->shownName() }}</td>
                            <td>
                                <span class="badge {{ $p->status === 'published' ? 'green' : ($p->status === 'rejected' ? 'red' : 'gray') }}">{{ $p->statusLabel() }}</span>
                            </td>
                            <td>{{ $p->created_at?->format('d-m-Y') }}</td>
                            <td><a class="btn small gray" href="{{ route('admin.projects.show', $p) }}">Tinjau</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $projects->links() }}</div>
    @endif
@endsection
