@extends('layouts.app')

@section('title', 'Kelola Saran')

@section('content')
    <h1>Kelola Saran</h1>

    <form method="GET" class="filters card">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                @foreach (\App\Models\Suggestion::STATUSES as $k => $l)
                    <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div><button class="btn" type="submit">Terapkan</button></div>
    </form>

    @forelse ($suggestions as $s)
        <article class="card" style="margin-bottom:14px">
            <h3>{{ $s->subject }} <span class="badge">{{ $s->statusLabel() }}</span></h3>
            <p class="muted" style="margin-top:0">
                {{ $s->created_at->format('d-m-Y H:i') }}
                &middot; {{ $s->name ?: ($s->user?->shownName() ?? 'Anonim') }}
                @if ($s->email) &middot; {{ $s->email }} @endif
            </p>
            <p style="white-space:pre-line">{{ $s->message }}</p>

            <form method="POST" action="{{ route('admin.suggestions.update', $s) }}">
                @csrf
                @method('PATCH')
                <div class="filters">
                    <div>
                        <label for="status-{{ $s->id }}">Status</label>
                        <select id="status-{{ $s->id }}" name="status">
                            @foreach (\App\Models\Suggestion::STATUSES as $k => $l)
                                <option value="{{ $k }}" @selected($s->status === $k)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:3 1 280px">
                        <label for="note-{{ $s->id }}">Catatan tindak lanjut</label>
                        <input id="note-{{ $s->id }}" name="admin_note" type="text" maxlength="2000" value="{{ $s->admin_note }}">
                    </div>
                    <div><button class="btn small" type="submit">Simpan</button></div>
                </div>
            </form>
            @if ($s->handled_at)
                <p class="hint">Terakhir ditangani {{ $s->handled_at->format('d-m-Y H:i') }} oleh {{ $s->handler?->shownName() }}.</p>
            @endif
        </article>
    @empty
        <p class="muted">Belum ada saran.</p>
    @endforelse

    <div class="pagination-wrap">{{ $suggestions->links() }}</div>
@endsection
