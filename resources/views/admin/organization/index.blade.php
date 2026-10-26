@extends('layouts.app')

@section('title', 'Kelola Struktur Organisasi')

@section('content')
    <div class="btn-row" style="justify-content:space-between">
        <h1>Struktur Organisasi dan Tim IT</h1>
        <a class="btn" href="{{ route('admin.organization.create') }}">+ Tambah anggota</a>
    </div>

    @foreach (\App\Models\OrgMember::GROUPS as $key => $label)
        @php($rows = $members->get($key, collect()))
        <section class="card" style="margin-top:16px">
            <div class="btn-row" style="justify-content:space-between">
                <h2 style="margin:0">{{ $label }}</h2>
                <a class="btn small gray" href="{{ route('admin.organization.create', ['group' => $key]) }}">+ Tambah</a>
            </div>
            @if ($key === 'students')
                <div class="alert warn" style="margin-top:12px">
                    Pastikan sudah ada izin mencantumkan nama siswa di situs publik.
                </div>
            @endif
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nama</th><th>Jabatan</th><th>Urutan</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($rows as $m)
                        <tr>
                            <td><a href="{{ route('admin.organization.edit', $m) }}">{{ $m->name }}</a></td>
                            <td>{{ $m->position }}</td>
                            <td>{{ $m->sort_order }}</td>
                            <td>
                                @if ($m->is_published)<span class="badge green">Tampil</span>
                                @else<span class="badge gray">Disembunyikan</span>@endif
                            </td>
                            <td><a class="btn small gray" href="{{ route('admin.organization.edit', $m) }}">Ubah</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Belum ada data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
@endsection
