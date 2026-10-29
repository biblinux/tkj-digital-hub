@extends('layouts.app')

@section('title', 'Tinjau Proyek')
@section('container_class', 'narrow')

@section('content')
    <p><a href="{{ route('admin.projects.index') }}">&larr; Daftar proyek</a></p>
    <h1>{{ $project->title }}</h1>
    <p>
        <span class="badge {{ $project->status === 'published' ? 'green' : ($project->status === 'rejected' ? 'red' : 'gray') }}">{{ $project->statusLabel() }}</span>
    </p>

    <section class="card">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="Gambar proyek {{ $project->title }}"
                 style="width:100%;max-height:320px;object-fit:cover;border-radius:8px;border:1px solid var(--line);margin-bottom:14px">
        @endif
        <dl class="details">
            <dt>Pemilik</dt><dd>{{ $project->user?->shownName() }} ({{ $project->user?->username }})</dd>
            <dt>Dikirim</dt><dd>{{ $project->created_at?->format('d-m-Y H:i') }}</dd>
            <dt>Deskripsi</dt><dd style="white-space:pre-line">{{ $project->description }}</dd>
            <dt>Teknologi</dt><dd>{{ implode(', ', $project->tech ?? []) ?: '-' }}</dd>
            <dt>Hasil</dt><dd>{{ $project->result ?: '-' }}</dd>
            <dt>Tautan</dt>
            <dd>
                @if ($project->link_url)
                    <a href="{{ $project->link_url }}" rel="noopener noreferrer nofollow ugc" target="_blank">{{ $project->link_url }}</a>
                    <span class="hint">Buka dengan hati-hati; tautan ditulis oleh siswa.</span>
                @else - @endif
            </dd>
            @if ($project->status === 'rejected')
                <dt>Alasan penolakan</dt><dd>{{ $project->reject_reason }}</dd>
            @endif
            @if ($project->reviewer)
                <dt>Ditinjau oleh</dt><dd>{{ $project->reviewer->shownName() }} pada {{ $project->reviewed_at?->format('d-m-Y H:i') }}</dd>
            @endif
        </dl>
    </section>

    <section class="card" style="margin-top:20px">
        <div class="btn-row">
            @if ($project->status !== 'published')
                <form method="POST" action="{{ route('admin.projects.approve', $project) }}">
                    @csrf
                    <button class="btn" type="submit">Setujui dan tampilkan</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}"
                  onsubmit="return confirm('Hapus proyek ini secara permanen?')">
                @csrf
                @method('DELETE')
                <button class="btn danger" type="submit">Hapus</button>
            </form>
        </div>

        @if ($project->status !== 'rejected')
            <form method="POST" action="{{ route('admin.projects.reject', $project) }}" style="margin-top:18px">
                @csrf
                <label for="reject_reason">Tolak dengan alasan (terlihat oleh pemilik)</label>
                <input id="reject_reason" name="reject_reason" type="text" maxlength="300" required value="{{ old('reject_reason') }}">
                @error('reject_reason')<p class="field-error">{{ $message }}</p>@enderror
                <div style="margin-top:12px"><button class="btn gray" type="submit">Tolak proyek</button></div>
            </form>
        @endif
    </section>
@endsection
