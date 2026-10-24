```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun - TKJ Digital Hub</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #fff7ed;
            color: #292524;
        }

        .card {
            width: 100%;
            max-width: 430px;
            padding: 30px;
            background: white;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        h1 {
            margin-top: 0;
            color: #c2410c;
            font-size: 25px;
        }

        .description {
            color: #57534e;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d6d3d1;
            border-radius: 7px;
            font-size: 15px;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #ea580c;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #c2410c;
        }

        .error {
            margin-top: 6px;
            color: #b91c1c;
            font-size: 14px;
        }

        .hint {
            margin-top: 8px;
            color: #78716c;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Aktivasi Akun</h1>

        <p class="description">
            Akun untuk <strong>{{ $email }}</strong> sedang menunggu aktivasi.
            Buat kata sandi baru untuk menyelesaikan proses aktivasi.
        </p>

        @if ($errors->any())
            <div class="error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST"
              action="{{ route('activation.activate', ['token' => $token]) }}">
            @csrf

            <label for="password">Kata Sandi Baru</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                minlength="12"
                required
            >
            <p class="hint">
                Minimal 12 karakter, mencakup huruf besar, huruf kecil,
                angka, dan simbol.
            </p>

            <label for="password_confirmation">Konfirmasi Kata Sandi</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                minlength="12"
                required
            >

            <button type="submit">Aktifkan Akun</button>
        </form>
    </main>
</body>
</html>
```
