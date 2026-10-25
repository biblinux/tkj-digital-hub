@extends('layouts.app')

@section('title', 'Masuk')
@section('container_class', 'narrow')

@section('content')
    <div class="center-page">
        <section class="card auth-card accent">
            <h1>Masuk</h1>
            <p class="lead">Masuk untuk mengakses layanan TKJ Digital Hub.</p>

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

            <p class="hint" style="margin-top:16px">Akun dibuat oleh administrator. Anda akan menerima email aktivasi untuk membuat kata sandi.</p>
        </section>
    </div>
@endsection
