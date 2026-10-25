@extends('layouts.app')

@section('title', 'Detail Pengguna')

@section('content')
    <p><a href="{{ route('admin.users.index') }}">&larr; Daftar pengguna</a></p>

    <h1>{{ $user->shownName() }}</h1>
    <p class="lead">
        <span class="badge">{{ $user->roleLabel() }}</span>
        @if ($user->is_active)<span class="badge green">Aktif</span>@else<span class="badge red">Belum aktif / nonaktif</span>@endif
    </p>

    <section class="card">
        <dl class="details">
            <dt>Nama</dt><dd>{{ $user->name }}</dd>
            <dt>Username</dt><dd>{{ $user->username }}</dd>
            <dt>Email</dt><dd>{{ $user->email }}</dd>
            <dt>Dibuat</dt><dd>{{ $user->created_at?->format('d-m-Y H:i') }}</dd>
            @if ($user->activation)
                <dt>Aktivasi</dt>
                <dd>
                    @if ($user->activation->used_at)
                        Diaktivasi {{ $user->activation->used_at->format('d-m-Y H:i') }}
                    @elseif ($user->activation->expires_at->isPast())
                        Tautan kedaluwarsa
                    @else
                        Menunggu aktivasi (berlaku sampai {{ $user->activation->expires_at->format('d-m-Y H:i') }})
                    @endif
                </dd>
            @endif
        </dl>

        <div class="btn-row" style="margin-top:18px">
            @can('update', $user)
                <a class="btn" href="{{ route('admin.users.edit', $user) }}">Ubah</a>
            @endcan

            @can('resendActivation', $user)
                <form method="POST" action="{{ route('admin.users.resend', $user) }}">
                    @csrf
                    <button class="btn gray" type="submit">Kirim ulang aktivasi</button>
                </form>
            @endcan

            @can('deactivate', $user)
                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                      onsubmit="return confirm('Nonaktifkan akun ini? Semua sesi login akan dihentikan.')">
                    @csrf
                    <button class="btn danger" type="submit">Nonaktifkan</button>
                </form>
            @endcan

            @can('reactivate', $user)
                <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                    @csrf
                    <button class="btn" type="submit">Aktifkan kembali</button>
                </form>
            @endcan
        </div>
    </section>

    @can('managePermissions', $user)
        <section class="card" style="margin-top:20px">
            <h2 style="margin-top:0">Izin admin</h2>
            <form method="POST" action="{{ route('admin.users.permissions', $user) }}">
                @csrf
                @method('PUT')
                @foreach ($permissions as $permission)
                    <label class="checkbox">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                               @checked($user->permissions->contains('id', $permission->id))>
                        <span><strong>{{ $permission->name }}</strong> &mdash; {{ $permission->description }}</span>
                    </label>
                @endforeach
                <div style="margin-top:14px"><button class="btn" type="submit">Simpan izin</button></div>
            </form>
        </section>
    @endcan
@endsection
