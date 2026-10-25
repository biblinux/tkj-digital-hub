<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun TKJ Digital Hub</title>
</head>
<body style="margin:0;padding:24px;background-color:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#292524;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e7e5e4;">
        <div style="background:#ea580c;color:#ffffff;padding:20px 32px;">
            <strong style="font-size:20px;">TKJ Digital Hub</strong>
        </div>

        <div style="padding:28px 32px;">
            <h2 style="margin-top:0;color:#9a3412;">Aktivasi Akun</h2>

            <p>Halo, {{ $name }}.</p>

            <p>
                Akun TKJ Digital Hub telah dibuat untuk Anda. Klik tombol di bawah
                untuk mengaktifkannya dan membuat kata sandi.
            </p>

            <p style="margin:28px 0;">
                <a href="{{ $activationUrl }}"
                   style="display:inline-block;background:#ea580c;color:#ffffff;text-decoration:none;padding:14px 24px;border-radius:8px;font-weight:bold;">
                    Aktifkan Akun
                </a>
            </p>

            <p>Tautan berlaku selama {{ $ttlMinutes }} menit dan hanya dapat digunakan satu kali.</p>

            <p>Jika tombol tidak berfungsi, salin tautan berikut ke browser:</p>
            <p style="word-break:break-all;font-size:13px;">
                <a href="{{ $activationUrl }}" style="color:#c2410c;">{{ $activationUrl }}</a>
            </p>

            <hr style="border:none;border-top:1px solid #e7e5e4;margin:28px 0;">

            <p style="font-size:12px;color:#78716c;">
                Email ini dikirim otomatis. Jika Anda tidak mengharapkan akun ini,
                abaikan email ini dan jangan bagikan tautan tersebut kepada siapa pun.
            </p>
        </div>
    </div>
</body>
</html>
