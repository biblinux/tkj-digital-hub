<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ config('tkj.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/tkj.css') }}">
</head>
<body>
    <main style="display:grid;place-items:center;padding:40px 16px">
        <section class="card accent" style="max-width:480px;text-align:center">
            <p class="error-code">{{ $code }}</p>
            <h1>{{ $title }}</h1>
            <p class="lead">{{ $text }}</p>
            <div class="btn-row" style="justify-content:center">
                <a class="btn" href="{{ url('/') }}">Ke beranda</a>
            </div>
        </section>
    </main>
</body>
</html>
