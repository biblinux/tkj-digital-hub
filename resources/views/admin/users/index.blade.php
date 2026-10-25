@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="btn-row" style="justify-content:space-between">
        <h1>Manajemen Pengguna</h1>
        <a class="btn" href="{{ route('admin.users.create') }}">+ Tambah pengguna</a>
    </div>

    <form method="GET" class="filters card">
        <div style="flex:2 1 240px">
            <label for="q">Cari nama, username, atau email</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="100">
        </div>
        <div>
            <label for="role">Peran</label>
            <select id="role" name="role">
                <option value="">Semua</option>
                @foreach (\App\Models\User::ROLES as $k => $l)
                    <option value="{{ $k }}" @selected(($filters['role'] ?? '') === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Belum aktif / nonaktif</option>
            </select>
        </div>
        <div><button class="btn" type="submit">Terapkan</button></div>
    </form>

    <section class="card">
        <p class="muted" style="margin-top:0">Total: {{ $users->total() }} akun</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Peran</th><th>Status</th><th>Dibuat</th></tr></thead>
                <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td><a href="{{ route('admin.users.show', $u) }}">{{ $u->display_name ?: $u->name }}</a></td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ \App\Models\User::ROLES[$u->role] ?? $u->role }}</td>
                        <td>
                            @if ($u->is_active)<span class="badge green">Aktif</span>
                            @else<span class="badge red">Belum aktif / nonaktif</span>@endif
                        </td>
                        <td>{{ $u->created_at?->format('d-m-Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada pengguna yang cocok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $users->links() }}</div>
    </section>
@endsection
