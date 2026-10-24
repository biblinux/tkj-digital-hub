<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TKJ Digital Hub</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        h1 {
            margin-top: 0;
            font-size: 24px;
        }

        p {
            color: #666;
            line-height: 1.5;
        }

        label {
            display: block;
            margin: 18px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            border: 0;
            border-radius: 6px;
            background: #f97316;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .error {
            padding: 10px;
            border-radius: 6px;
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <main class="login-card">
        <h1>TKJ Digital Hub</h1>
        <p>Masuk untuk mengakses layanan informasi dan helpdesk TKJ.</p>

        @if ($errors->any())
            <div class="error" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <label for="login">Username atau email</label>
            <input
                id="login"
                name="login"
                type="text"
                value="{{ old('login') }}"
                autocomplete="username"
                required
                autofocus
            >

            <label for="password">Kata sandi</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Masuk</button>
        </form>
    </main>
</body>
</html>
