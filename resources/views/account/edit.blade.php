@extends('layouts.app')

@section('title', 'Akun Saya')
@section('container_class', 'narrow')

@section('content')
    <h1>Akun Saya</h1>

    <section class="card">
        <h2 style="margin-top:0">Foto profil</h2>
        @if ($user->photoUrl())
            <img src="{{ $user->photoUrl() }}" alt="Foto profil {{ $user->shownName() }}"
                 style="width:96px;height:96px;object-fit:cover;border-radius:50%;display:block;margin-bottom:12px">
        @else
            <p class="hint">Belum ada foto.</p>
        @endif
        <form method="POST" action="{{ route('account.photo') }}" enctype="multipart/form-data">
            @csrf
            <label for="photo">Pilih foto (JPG, PNG, atau WebP, maksimal 2 MB)</label>
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
            @error('photo')<p class="error">{{ $message }}</p>@enderror
            <div class="btn-row" style="margin-top:14px"><button class="btn" type="submit">Unggah foto</button></div>
        </form>
        @if ($user->photoUrl())
            <form method="POST" action="{{ route('account.photo.remove') }}" style="margin-top:10px">
                @csrf
                <button class="btn gray" type="submit">Hapus foto</button>
            </form>
        @endif
    </section>

    <form method="POST" action="{{ route('account.update') }}" class="card" style="margin-top:20px">
        @csrf
        <h2 style="margin-top:0">Data akun</h2>

        <label>Nama resmi</label>
        <input type="text" value="{{ $user->name }}" disabled>
        <p class="hint">Nama resmi dan username diubah oleh admin.</p>

        <label>Username</label>
        <input type="text" value="{{ $user->username }}" disabled>

        <label for="display_name">Nama tampilan</label>
        <input id="display_name" name="display_name" type="text" maxlength="100" value="{{ old('display_name', $user->display_name) }}">
        @error('display_name')<p class="error">{{ $message }}</p>@enderror

        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="255" required autocomplete="email" value="{{ old('email', $user->email) }}">
        @error('email')<p class="error">{{ $message }}</p>@enderror

        <label for="current_password">Kata sandi saat ini (wajib hanya bila mengganti email)</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" maxlength="255">
        @error('current_password')<p class="error">{{ $message }}</p>@enderror

        <div class="btn-row" style="margin-top:20px"><button class="btn" type="submit">Simpan</button></div>
    </form>
@endsection
