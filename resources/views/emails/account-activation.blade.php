<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun TKJ Digital Hub</title>
</head>
<body style="margin:0;padding:24px;background-color:#f5f5f5;font-family:Arial,sans-serif;color:#333;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;padding:32px;border-radius:12px;">
        <h1 style="color:#ea580c;margin-top:0;">TKJ Digital Hub</h1>

        <h2>Aktivasi Akun</h2>

        <p>Halo, {{ $name }}.</p>

        <p>
            Akun TKJ Digital Hub telah dibuat untuk Anda.
            Untuk mengaktifkannya, klik tombol di bawah ini dan buat kata sandi.
        </p>

        <p style="margin:28px 0;">
            <a href="{{ $activationUrl }}"
               style="display:inline-block;background:#ea580c;color:#ffffff;text-decoration:none;padding:14px 24px;border-radius:8px;font-weight:bold;">
                Aktifkan Akun
            </a>
        </p>

        <p>Tautan aktivasi berlaku selama 60 menit dan hanya dapat digunakan satu kali.</p>

        <p>Jika tombol tidak berfungsi, salin tautan berikut ke browser:</p>
        <p style="word-break:break-all;">
            <a href="{{ $activationUrl }}">{{ $activationUrl }}</a>
        </p>

        <hr style="border:none;border-top:1px solid #eeeeee;margin:28px 0;">

        <p style="font-size:12px;color:#777;">
            Email otomatis dari TKJ Digital Hub. Jika Anda tidak mengharapkan akun ini,
            abaikan email ini.
        </p>
    </div>
</body>
</html>
