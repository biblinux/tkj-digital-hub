<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TKJ Digital Hub</title>
</head>
<body>
    <h1>Dashboard TKJ Digital Hub</h1>

    <p>
        Selamat datang,
        {{ auth()->user()->display_name ?: auth()->user()->name }}.
    </p>

    <p>Peran: {{ auth()->user()->role }}</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Keluar</button>
    </form>
</body>
</html>
