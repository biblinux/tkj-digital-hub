@extends('layouts.app')

@section('title', 'Kontak')

@section('content')
    <h1>Kontak dan Informasi Sekolah</h1>

    @php($school = config('tkj.school'))

    @if ($school['name'] || $school['address'] || $school['phone'] || $school['email'])
        <div class="card">
            <dl class="details">
                @if ($school['name'])<dt>Sekolah</dt><dd>{{ $school['name'] }}</dd>@endif
                @if ($school['address'])<dt>Alamat</dt><dd>{{ $school['address'] }}</dd>@endif
                @if ($school['phone'])<dt>Telepon</dt><dd>{{ $school['phone'] }}</dd>@endif
                @if ($school['email'])<dt>Email</dt><dd><a href="mailto:{{ $school['email'] }}">{{ $school['email'] }}</a></dd>@endif
                @if ($school['map_url'] && preg_match('#^https://#', $school['map_url']))
                    <dt>Peta</dt><dd><a href="{{ $school['map_url'] }}" rel="noopener noreferrer">Buka peta lokasi</a></dd>
                @endif
            </dl>
        </div>
    @else
        @include('partials.empty', ['title' => 'Informasi kontak belum diisi', 'text' => 'Isi SCHOOL_NAME, SCHOOL_ADDRESS, SCHOOL_PHONE, dan SCHOOL_EMAIL pada file .env.'])
    @endif

    <p style="margin-top:20px">Ada masukan? <a href="{{ route('suggestions.create') }}">Kirim saran</a>.</p>
@endsection
