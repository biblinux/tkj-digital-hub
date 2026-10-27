@extends('layouts.app')

@section('title', 'Masuk')
@section('container_class', 'narrow')

@section('content')
    <div class="center-page">
        <section class="card auth-card accent">
            <h1>Masuk Admin</h1>
            <p class="lead">Dashboard pengelola TKJ Digital Hub, khusus admin dan teknisi.</p>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <label for="login">Username atau email</label>
                <input id="login" name="login" type="text" value="{{ old('login') }}"
                       autocomplete="username" required autofocus maxlength="255">

                <label for="password">Kata sandi</label>
                <input id="password" name="password" type="password"
                       autocomplete="current-password" required maxlength="255">

                <div style="margin-top:22px">
                    <button class="btn" type="submit" style="width:100%">Masuk</button>
                </div>
            </form>

            <p class="hint" style="margin-top:16px">Siswa tidak masuk di sini: gunakan tombol Masuk di situs utama jarvar.web.id. Akun dibuat oleh administrator dan diaktifkan lewat tautan aktivasi.</p>
        </section>
    </div>
@endsection
