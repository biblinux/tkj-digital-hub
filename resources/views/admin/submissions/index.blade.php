@extends('layouts.app')

@section('title', 'Persetujuan Kiriman')

@section('content')
    <h1>Persetujuan Kiriman</h1>
    <p class="muted">Berita dan foto galeri yang dikirim pengguna dari situs. Hanya yang disetujui yang tampil publik.</p>

    @php
        $badge = fn ($s) => $s === 'approved' ? '<span class="badge green">Disetujui</span>' : ($s === 'rejected' ? '<span class="badge gray">Ditolak</span>' : '<span class="badge">Menunggu</span>');
    @endphp

    @foreach ([['news', 'Berita', $news], ['gallery', 'Foto galeri', $gallery]] as [$type, $heading, $items])
        <section class="card" style="margin-bottom:16px">
            <h2>{{ $heading }}</h2>
            @forelse ($items as $item)
                <article style="border-top:1px solid var(--line);padding:14px 0">
                    <div style="display:flex;gap:14px;flex-wrap:wrap">
                        @if ($item->imageUrl())
                            <img src="{{ $item->imageUrl() }}" alt="" style="width:140px;max-height:110px;object-fit:cover;border-radius:8px">
                        @endif
                        <div style="flex:1 1 260px;min-width:0;overflow-wrap:anywhere">
                            <strong>{{ $type === 'news' ? $item->title : $item->caption }}</strong> {!! $badge($item->review_status) !!}
                            <div class="muted">Oleh {{ $item->submitter?->shownName() ?? '-' }}@if ($item->submitter?->class_name) ({{ $item->submitter->class_name }})@endif &middot; {{ $item->created_at->format('d-m-Y H:i') }}@if ($type === 'news') &middot; {{ $item->category }}@endif</div>
                            @if ($type === 'news')
                                <p style="margin:6px 0">{{ $item->summary }}</p>
                                @if ($item->body)<details><summary>Isi berita</summary><p style="white-space:pre-line">{{ $item->body }}</p></details>@endif
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.submissions.review', [$type, $item->id]) }}" class="btn-row" style="margin-top:10px;align-items:flex-end">
                        @csrf
                        <div style="flex:1 1 220px"><label for="note-{{ $type }}-{{ $item->id }}">Catatan (saat menolak)</label>
                            <input id="note-{{ $type }}-{{ $item->id }}" name="review_note" type="text" maxlength="300" value="{{ $item->review_note }}"></div>
                        <button class="btn" type="submit" name="decision" value="approved">Setujui</button>
                        <button class="btn gray" type="submit" name="decision" value="rejected">Tolak</button>
                    </form>
                </article>
            @empty
                <p class="muted">Belum ada kiriman.</p>
            @endforelse
        </section>
    @endforeach
@endsection
