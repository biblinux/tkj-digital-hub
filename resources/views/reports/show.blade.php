@extends('layouts.app')

@section('title', 'Laporan '.$report->reference)

@section('content')
    <p><a href="{{ $canHandle ? route('admin.reports.index') : route('reports.index') }}">&larr; Kembali</a></p>

    <h1>{{ $report->title }}</h1>
    <p class="lead">
        {{ $report->reference }}
        <span class="badge blue">{{ $report->statusLabel() }}</span>
        <span class="badge {{ in_array($report->urgency, ['high', 'urgent']) ? 'red' : 'gray' }}">Urgensi: {{ $report->urgencyLabel() }}</span>
    </p>

    <div class="grid cols-2">
        <section class="card">
            <h3>Detail</h3>
            <dl class="details">
                <dt>Kategori</dt><dd>{{ $report->categoryLabel() }}</dd>
                <dt>Pelapor</dt><dd>{{ $report->reporter?->shownName() }}</dd>
                <dt>Lokasi</dt><dd>{{ $report->location ?: '-' }}</dd>
                <dt>Teknisi</dt><dd>{{ $report->assignee?->shownName() ?? 'Belum ditugaskan' }}</dd>
                <dt>Dibuat</dt><dd>{{ $report->created_at->format('d-m-Y H:i') }}</dd>
                <dt>Diperbarui</dt><dd>{{ $report->updated_at->format('d-m-Y H:i') }}</dd>
                @if ($report->resolved_at)<dt>Selesai</dt><dd>{{ $report->resolved_at->format('d-m-Y H:i') }}</dd>@endif
            </dl>
            <h3 style="margin-top:16px">Uraian</h3>
            <p style="white-space:pre-line;margin:0">{{ $report->description }}</p>
        </section>

        <section class="card">
            <h3>Riwayat</h3>
            <ul class="timeline">
                @foreach ($updates as $u)
                    <li class="{{ $u->is_internal ? 'internal' : '' }}">
                        <strong>{{ $u->author?->shownName() ?? 'Sistem' }}</strong>
                        <span class="muted"> &middot; {{ $u->created_at->format('d-m-Y H:i') }}</span>
                        @if ($u->is_internal) <span class="badge gray">Internal</span> @endif
                        @if ($u->to_status)
                            <div>Status: {{ \App\Models\ItReport::STATUSES[$u->from_status] ?? '-' }} &rarr; <strong>{{ \App\Models\ItReport::STATUSES[$u->to_status] ?? $u->to_status }}</strong></div>
                        @endif
                        @if ($u->note)<div style="white-space:pre-line">{{ $u->note }}</div>@endif
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    @if ($canHandle)
        <section class="card accent" style="margin-top:20px">
            <h2 style="margin-top:0">Tangani laporan</h2>
            <form method="POST" action="{{ route('reports.handle', $report) }}">
                @csrf

                <label for="status">Status</label>
                <select id="status" name="status" required>
                    @foreach (\App\Models\ItReport::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected($report->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                @if ($canAssign)
                    <label for="assigned_to">Teknisi</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">Belum ditugaskan</option>
                        @foreach ($technicians as $t)
                            <option value="{{ $t->id }}" @selected($report->assigned_to === $t->id)>{{ $t->shownName() }}</option>
                        @endforeach
                    </select>
                @endif

                <label for="note">Catatan tindak lanjut</label>
                <textarea id="note" name="note" maxlength="2000">{{ old('note') }}</textarea>

                <label class="checkbox"><input type="checkbox" name="is_internal" value="1"> Catatan internal (tidak terlihat pelapor)</label>

                <button class="btn" type="submit">Simpan</button>
            </form>
        </section>
    @endif

    @if ($isOwner && ! $report->isFinal())
        <section class="card" style="margin-top:20px">
            <h2 style="margin-top:0">Tambah keterangan</h2>
            <form method="POST" action="{{ route('reports.comment', $report) }}">
                @csrf
                <label for="comment">Keterangan tambahan</label>
                <textarea id="comment" name="note" required maxlength="2000"></textarea>
                <div style="margin-top:14px"><button class="btn" type="submit">Kirim keterangan</button></div>
            </form>
        </section>
    @endif
@endsection
