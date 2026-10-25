@extends('layouts.app')

@section('title', 'Ubah Pengguna')
@section('container_class', 'narrow')

@section('content')
    <h1>Ubah Pengguna</h1>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card">
        @csrf
        @method('PUT')
        @include('admin.users._fields', ['user' => $user])
        <div class="btn-row" style="margin-top:20px">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn gray" href="{{ route('admin.users.show', $user) }}">Batal</a>
        </div>
    </form>
@endsection
