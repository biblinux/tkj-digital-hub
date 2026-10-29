@extends('layouts.app')

@section('title', 'Profil Jurusan')
@section('container_class', 'narrow')

@section('content')
    <h1>Profil Jurusan</h1>
    <p class="lead">Teks ini tampil di halaman Profil situs publik. Bagian yang dikosongkan tidak ditampilkan.</p>

    <form method="POST" action="{{ route('admin.site.update') }}" class="card">
        @csrf
        @method('PUT')

        <label for="vision">Visi</label>
        <textarea id="vision" name="vision" maxlength="600" style="min-height:100px">{{ old('vision', $values['vision'] ?? '') }}</textarea>
        @error('vision')<p class="field-error">{{ $message }}</p>@enderror

        <label for="mission">Misi (satu butir per baris, maksimal 10)</label>
        <textarea id="mission" name="mission" maxlength="2000" style="min-height:140px">{{ old('mission', $values['mission'] ?? '') }}</textarea>
        @error('mission')<p class="field-error">{{ $message }}</p>@enderror

        <label for="about">Tentang jurusan</label>
        <textarea id="about" name="about" maxlength="3000" style="min-height:160px">{{ old('about', $values['about'] ?? '') }}</textarea>
        @error('about')<p class="field-error">{{ $message }}</p>@enderror

        <label for="competencies">Kompetensi keahlian (satu per baris, format: Judul | Penjelasan, maksimal 10)</label>
        <textarea id="competencies" name="competencies" maxlength="3000" style="min-height:140px">{{ old('competencies', $values['competencies'] ?? '') }}</textarea>
        <p class="hint">Contoh: Jaringan komputer | Merancang dan memelihara jaringan LAN.</p>
        @error('competencies')<p class="field-error">{{ $message }}</p>@enderror

        <h2>Kontak</h2>
        <label for="address">Alamat</label>
        <input id="address" name="address" type="text" maxlength="300" value="{{ old('address', $values['address'] ?? '') }}">
        <label for="phone">Telepon</label>
        <input id="phone" name="phone" type="text" maxlength="40" value="{{ old('phone', $values['phone'] ?? '') }}">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $values['email'] ?? '') }}">
        @error('email')<p class="field-error">{{ $message }}</p>@enderror
        <label for="map_url">Tautan peta (opsional)</label>
        <input id="map_url" name="map_url" type="text" maxlength="500" value="{{ old('map_url', $values['map_url'] ?? '') }}">
        @error('map_url')<p class="field-error">{{ $message }}</p>@enderror

        <div class="btn-row" style="margin-top:20px"><button class="btn" type="submit">Simpan</button></div>
    </form>
@endsection
