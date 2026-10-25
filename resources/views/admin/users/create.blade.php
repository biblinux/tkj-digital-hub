@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('container_class', 'narrow')

@section('content')
    <h1>Tambah Pengguna</h1>
    <p class="alert info">Akun baru berstatus belum aktif. Pengguna menerima email berisi tautan aktivasi (berlaku {{ config('tkj.activation_ttl_minutes') }} menit) untuk membuat kata sandi sendiri.</p>

    <form method="POST" action="{{ route('admin.users.store') }}" class="card">
        @csrf
        @include('admin.users._fields', ['user' => null])
        <div class="btn-row" style="margin-top:20px">
            <button class="btn" type="submit">Buat akun</button>
            <a class="btn gray" href="{{ route('admin.users.index') }}">Batal</a>
        </div>
    </form>
@endsection
