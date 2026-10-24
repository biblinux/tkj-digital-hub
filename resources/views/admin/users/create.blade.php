<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna - TKJ Digital Hub</title>
    <style>
        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            color: #222;
        }
        main {
            max-width: 650px;
            margin: auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
        }
        label {
            display: block;
            margin: 18px 0 6px;
            font-weight: bold;
        }
        input, select {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button, .back {
            display: inline-block;
            margin-top: 20px;
            padding: 11px 16px;
            border: 0;
            border-radius: 6px;
            background: #f97316;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        .back {
            background: #555;
        }
        .error {
            color: #b91c1c;
            font-size: 14px;
        }
        .notice {
            padding: 12px;
            background: #fff7ed;
            border-radius: 6px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
<main>
    <h1>Tambah Pengguna</h1>
    <p class="notice">
        Akun baru akan berstatus belum aktif. Pengguna nantinya harus
        mengaktifkan akun melalui email dan menetapkan password sendiri.
    </p>

    @if ($errors->any())
        <div class="error">
            <p>Periksa kembali data yang dimasukkan:</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <label for="name">Nama lengkap</label>
        <input
            id="name"
            name="name"
            value="{{ old('name') }}"
            maxlength="100"
            required
        >

        <label for="username">Username</label>
        <input
            id="username"
            name="username"
            value="{{ old('username') }}"
            minlength="3"
            maxlength="30"
            pattern="[A-Za-z0-9._-]+"
            required
        >

        <label for="email">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            value="{{ old('email') }}"
            maxlength="255"
            required
        >

        <label for="role">Peran pengguna</label>
        <select id="role" name="role" required>
            <option value="student" @selected(old('role') === 'student')>
                Siswa
            </option>
            <option value="technician" @selected(old('role') === 'technician')>
                Teknisi
            </option>

            @if (auth()->user()->role === 'super_admin')
                <option value="admin" @selected(old('role') === 'admin')>
                    Admin
                </option>
            @endif
        </select>

        <button type="submit">Buat Akun</button>
        <a class="back" href="{{ route('admin.users.index') }}">Kembali</a>
    </form>
</main>
</body>
</html>

