<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - TKJ Digital Hub</title>

    <style>
        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            color: #222;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 8px;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            background: #f97316;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .card {
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px #0000000d;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #fff7ed;
        }

        .status {
            font-weight: bold;
        }

        .active {
            color: #15803d;
        }

        .inactive {
            color: #b91c1c;
        }

        .pagination {
            margin-top: 20px;
        }

        .pagination nav div {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="header">
            <div>
                <h1>Manajemen Pengguna</h1>
                <p>TKJ Digital Hub — Daftar akun pengguna.</p>
            </div>

            <a href="{{ route('dashboard') }}" class="button">
                Kembali ke Dashboard
            </a>

	    <a href="{{ route('admin.users.create') }}" class="button">
                + Tambah Pengguna
            </a>
        </div>

        <section class="card">
            <p>Total akun: {{ $users->total() }}</p>

            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                {{ $user->display_name ?: $user->name }}
                            </td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role }}</td>
                            <td>
                                @if ($user->is_active)
                                    <span class="status active">Aktif</span>
                                @else
                                    <span class="status inactive">Belum aktif</span>
                                @endif
                            </td>
                            <td>
                                {{ $user->created_at->format('d-m-Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Belum ada pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="pagination">
                {{ $users->links() }}
            </div>
        </section>
    </main>
</body>
</html>
