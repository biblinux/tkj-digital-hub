@extends('layouts.app')

@section('title', 'Aktivasi Akun')
@section('container_class', 'narrow')

@section('content')
    <div class="center-page">
        <section class="card auth-card accent">
            <h1>Aktivasi Akun</h1>
            <p class="lead" style="overflow-wrap:anywhere">
                Akun untuk <strong>{{ $email }}</strong> menunggu aktivasi. Buat kata sandi baru untuk menyelesaikannya.
            </p>

            <form method="POST" action="{{ route('activation.activate', ['token' => $token]) }}">
                @csrf

                <label for="password">Kata sandi baru</label>
                <input id="password" name="password" type="password" autocomplete="new-password"
                       minlength="12" required aria-describedby="password-hint">
                <p class="hint" id="password-hint">Minimal 12 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.</p>

                <label for="password_confirmation">Konfirmasi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       autocomplete="new-password" minlength="12" required>

                <div style="margin-top:22px">
                    <button class="btn" type="submit" style="width:100%">Aktifkan akun</button>
                </div>
            </form>
        </section>
    </div>
@endsection
