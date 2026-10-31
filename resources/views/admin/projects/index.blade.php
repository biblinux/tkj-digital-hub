@extends('layouts.app')

@section('title', 'Proyek Siswa')

@section('content')
    <h1>Proyek Siswa</h1>
    <p class="muted">Proyek dikirim siswa dari situs. Tinjau lalu setujui agar tampil di halaman Proyek Siswa.
        @if ($pending) <strong>{{ $pending }} menunggu tinjauan.</strong> @endif</p>

    <form method="GET" class="filters card">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                @foreach (\App\Models\StudentProject::STATUSES as $k => $l)
                    <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div><button class="btn" type="submit">Terapkan</button></div>
    </form>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Judul</th><th>Pengirim</th><th>Status</th><th>Dikirim</th><th></th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td><a href="{{ route('admin.projects.show', $item) }}">{{ $item->title }}</a></td>
                        <td>{{ $item->user?->shownName() ?? '-' }}@if ($item->user?->class_name) ({{ $item->user->class_name }})@endif</td>
                        <td>
                            @if ($item->status === 'published')<span class="badge green">Terbit</span>
                            @elseif ($item->status === 'rejected')<span class="badge gray">Ditolak</span>
                            @else<span class="badge">Menunggu</span>@endif
                        </td>
                        <td>{{ $item->created_at->format('d-m-Y') }}</td>
                        <td><a class="btn small gray" href="{{ route('admin.projects.show', $item) }}">Tinjau</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada proyek.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $items->links() }}</div>
    </section>
@endsection
