@extends('layouts.app')

@section('title', $heading)
@section('container_class', 'narrow')

@section('content')
    <h1>{{ $heading }}</h1>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card">
        @csrf
        @include($form, ['item' => $item])
        <div class="btn-row" style="margin-top:20px">
            <button class="btn" type="submit">Simpan</button>
            <a class="btn gray" href="{{ $back }}">Batal</a>
        </div>
    </form>
@endsection
