@extends('layouts.app')

@section('title', 'Tinjau Proyek')

@section('content')
    <p><a href="{{ route('admin.projects.index') }}">&larr; Daftar proyek</a></p>
    <h1>{{ $project->title }}</h1>
    <p class="muted">
        Oleh {{ $project->user?->shownName() ?? '-' }}@if ($project->user?->class_name) ({{ $project->user->class_name }})@endif
        &middot; {{ $project->created_at->format('d-m-Y H:i') }}
        &middot; {{ \App\Models\StudentProject::STATUSES[$project->status] ?? $project->status }}
    </p>

    <section class="card">
        @if ($project->imageUrl())
            <p><img src="{{ $project->imageUrl() }}" alt="Foto proyek" style="max-width:100%;max-height:320px;border-radius:8px;border:1px solid var(--line)"></p>
        @endif
        <p style="white-space:pre-line">{{ $project->description }}</p>
        @if ($project->technologies)<p><strong>Teknologi:</strong> {{ $project->technologies }}</p>@endif
        @if ($project->result)<p><strong>Hasil:</strong> {{ $project->result }}</p>@endif
        @if ($project->link_url)<p><strong>Tautan:</strong> <a href="{{ $project->link_url }}" rel="noopener noreferrer nofollow" target="_blank">{{ $project->link_url }}</a></p>@endif
    </section>

    <section class="card" style="margin-top:14px">
        <h2>Keputusan</h2>
        <form method="POST" action="{{ route('admin.projects.review', $project) }}">
            @csrf
            <label for="review_note">Catatan untuk siswa <span class="hint">(opsional, dipakai saat menolak)</span></label>
            <input id="review_note" name="review_note" type="text" maxlength="300" value="{{ old('review_note', $project->review_note) }}">
            <div class="btn-row" style="margin-top:12px">
                <button class="btn" type="submit" name="decision" value="published">Setujui dan terbitkan</button>
                <button class="btn gray" type="submit" name="decision" value="rejected">Tolak</button>
                <button class="btn gray" type="submit" name="decision" value="pending">Kembalikan ke menunggu</button>
            </div>
        </form>
        <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" style="margin-top:16px"
              onsubmit="return confirm('Hapus proyek ini secara permanen?')">
            @csrf
            @method('DELETE')
            <button class="btn danger" type="submit">Hapus proyek</button>
        </form>
    </section>
@endsection
