@extends('layouts.app')

@section('title', 'Buat Laporan Masalah IT')
@section('container_class', 'narrow')

@section('content')
    <h1>Laporan Masalah IT</h1>
    <p class="lead">Jelaskan masalah perangkat atau jaringan yang Anda alami. Tim teknisi akan menindaklanjuti.</p>

    <form method="POST" action="{{ route('reports.store') }}" class="card">
        @csrf

        <label for="category">Kategori</label>
        <select id="category" name="category" required>
            @foreach (\App\Models\ItReport::CATEGORIES as $key => $label)
                <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <label for="title">Judul singkat</label>
        <input id="title" name="title" type="text" value="{{ old('title') }}" required minlength="5" maxlength="150">

        <label for="description">Uraian masalah</label>
        <textarea id="description" name="description" required minlength="10" maxlength="3000">{{ old('description') }}</textarea>
        <p class="hint">Sebutkan apa yang terjadi, kapan mulai, dan langkah yang sudah dicoba.</p>

        <label for="location">Lokasi / perangkat (opsional)</label>
        <input id="location" name="location" type="text" value="{{ old('location') }}" maxlength="150">

        <label for="urgency">Tingkat urgensi</label>
        <select id="urgency" name="urgency" required>
            @foreach (\App\Models\ItReport::URGENCIES as $key => $label)
                <option value="{{ $key }}" @selected(old('urgency', 'normal') === $key)>{{ $label }}</option>
            @endforeach
        </select>

        <div class="btn-row" style="margin-top:20px">
            <button class="btn" type="submit">Kirim laporan</button>
            <a class="btn gray" href="{{ route('reports.index') }}">Batal</a>
        </div>
    </form>
@endsection
