@extends('layouts.app')

@section('title', 'Kelola Laporan IT')

@section('content')
    <h1>Kelola Laporan IT</h1>

    <form method="GET" class="filters card">
        <div>
            <label for="q">Cari</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Judul atau nomor">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                @foreach (\App\Models\ItReport::STATUSES as $k => $l)
                    <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="urgency">Urgensi</label>
            <select id="urgency" name="urgency">
                <option value="">Semua</option>
                @foreach (\App\Models\ItReport::URGENCIES as $k => $l)
                    <option value="{{ $k }}" @selected(($filters['urgency'] ?? '') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div><button class="btn" type="submit">Terapkan</button></div>
    </form>

    <div class="card table-wrap">
        <table>
            <thead><tr><th>Nomor</th><th>Judul</th><th>Pelapor</th><th>Urgensi</th><th>Teknisi</th><th>Status</th><th>Dibuat</th></tr></thead>
            <tbody>
            @forelse ($reports as $r)
                <tr>
                    <td><a href="{{ route('reports.show', $r) }}">{{ $r->reference }}</a></td>
                    <td>{{ $r->title }}</td>
                    <td>{{ $r->reporter?->shownName() }}</td>
                    <td><span class="badge {{ in_array($r->urgency, ['high', 'urgent']) ? 'red' : 'gray' }}">{{ $r->urgencyLabel() }}</span></td>
                    <td>{{ $r->assignee?->shownName() ?? '-' }}</td>
                    <td><span class="badge blue">{{ $r->statusLabel() }}</span></td>
                    <td>{{ $r->created_at->format('d-m-Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada laporan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $reports->links() }}</div>
@endsection
