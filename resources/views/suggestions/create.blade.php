@extends('layouts.app')

@section('title', 'Kirim Saran')
@section('container_class', 'narrow')

@section('content')
    <h1>Kirim Saran</h1>
    <p class="lead">Sampaikan ide, masukan, atau kritik membangun untuk jurusan TKJ. Nama dan email boleh dikosongkan.</p>

    <form method="POST" action="{{ route('suggestions.store') }}" class="card">
        @csrf

        {{-- Honeypot anti-spam: jangan diisi oleh manusia. --}}
        <div class="hp" aria-hidden="true">
            <label for="website">Jangan diisi</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <label for="name">Nama (opsional)</label>
        <input id="name" name="name" type="text" value="{{ old('name', auth()->user()?->shownName()) }}" maxlength="100">

        <label for="email">Email (opsional)</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255">
        <p class="hint">Hanya diisi bila Anda ingin dihubungi kembali.</p>

        <label for="subject">Judul saran</label>
        <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required minlength="3" maxlength="150">

        <label for="message">Isi saran</label>
        <textarea id="message" name="message" required minlength="10" maxlength="2000">{{ old('message') }}</textarea>

        <div style="margin-top:20px"><button class="btn" type="submit">Kirim saran</button></div>
    </form>
@endsection
