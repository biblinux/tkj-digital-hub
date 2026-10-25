# TKJ Digital Hub

Website informasi resmi jurusan Teknik Komputer dan Jaringan (TKJ), dibangun dengan Laravel.

## Fitur

- Halaman publik: beranda, profil, kompetensi, kepengurusan, tim IT, topologi, galeri, fasilitas, kontak.
- Formulir saran (publik, dengan pembatasan percobaan) dan laporan masalah IT (wajib login).
- Dashboard sesuai role: `student`, `technician`, `admin`, `super_admin`.
- Laporan IT: kategori, urgensi, status, penugasan teknisi, catatan, dan riwayat perubahan status.
- Manajemen pengguna: daftar, pencarian, filter, detail, ubah, nonaktifkan, kelola permission.
- Aktivasi akun lewat tautan email berbatas waktu (token disimpan sebagai hash).
- Halaman error 403, 404, 419, 429, 500, dan 503.

Data sekolah yang belum tersedia (nama, alamat, kontak) ditampilkan sebagai "belum diisi".
Isi lewat variabel `SCHOOL_*` di `.env` atau di `config/tkj.php`.

## Menjalankan secara lokal

Kebutuhan: PHP 8.3+, Composer, Node.js (opsional, untuk aset Vite), SQLite.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate          # aman: hanya menambah, tidak menghapus data
php artisan db:seed          # idempotent; menambah akun uji hanya di APP_ENV=local
php artisan tkj:create-super-admin   # membuat akun super admin pertama
php artisan serve
```

Buka http://localhost:8000. Email aktivasi pada mode `MAIL_MAILER=log` ditulis ke
`storage/logs/laravel.log`.

> Jangan menjalankan `migrate:fresh` atau `migrate:refresh` pada database yang berisi data.

## API untuk frontend statis

Frontend statis di jarvar.web.id dapat memakai API JSON di `/api/v1` (login token, saran, laporan IT).
Lihat [docs/API.md](docs/API.md).

## Pengujian

```bash
php artisan test
```

Pengujian memakai SQLite di memori (lihat `phpunit.xml`), sehingga database
pengembangan tidak tersentuh. Mailer pengujian adalah `array`, jadi tidak ada email sungguhan yang dikirim.

## Email

Konfigurasi email sepenuhnya lewat `.env`. Untuk produksi:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.penyedia-anda.example
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="noreply@jarvar.web.id"
MAIL_FROM_NAME="TKJ Digital Hub"
```

Alamat `noreply@jarvar.web.id` baru boleh dipakai setelah domain diverifikasi di penyedia
email (SPF, DKIM, DMARC). Jangan menaruh kredensial SMTP di kode atau di Git.

## Deployment

Lihat [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
