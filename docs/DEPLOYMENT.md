# Panduan Deployment TKJ Digital Hub

Panduan untuk server Ubuntu 24.04 dengan Nginx dan PHP-FPM. Domain `jarvar.web.id`
belum diarahkan ke aplikasi; langkah DNS dan HTTPS di bawah dilakukan manual setelah
domain siap.

## 1. Persyaratan server

- PHP 8.3 dengan ekstensi: mbstring, xml, curl, sqlite3 (atau driver database lain), zip, bcmath, intl.
- Composer 2, Nginx, Git.
- Pengguna non-root untuk menjalankan aplikasi.

## 2. Instalasi

```bash
git clone <url-repositori> /var/www/tkj-digital-hub
cd /var/www/tkj-digital-hub
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

## 3. Environment (`.env`)

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jarvar.web.id
SESSION_SECURE_COOKIE=true
```

Jika frontend statis dihosting terpisah, isi juga `CORS_ALLOWED_ORIGINS` (lihat `docs/API.md`).

`APP_DEBUG` wajib `false` di produksi agar detail error tidak bocor. Isi juga `MAIL_*`
(lihat README) dan `SCHOOL_*`.

## 4. Database dan migrasi aman

```bash
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force
php artisan tkj:create-super-admin
```

Jangan pernah memakai `migrate:fresh` atau `migrate:refresh` di produksi. Sebelum migrasi,
selalu buat cadangan (bagian 9).

## 5. Permission direktori

```bash
sudo chown -R www-data:www-data storage bootstrap/cache database
sudo chmod -R ug+rwX storage bootstrap/cache database
```

## 6. Nginx

```nginx
server {
    listen 80;
    server_name jarvar.web.id;
    root /var/www/tkj-digital-hub/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

## 7. HTTPS

Setelah DNS mengarah ke server:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d jarvar.web.id
```

## 8. Cache, queue, dan scheduler

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Setelah mengubah `.env`, jalankan ulang `php artisan config:cache`.

Antrean memakai driver `database`. Jika email dikirim lewat antrean, jalankan worker
(misalnya dengan systemd atau Supervisor): `php artisan queue:work --tries=3`.
Aplikasi ini tidak memerlukan scheduler.

## 9. Backup dan pemulihan

Cadangkan `.env`, `database/database.sqlite`, dan `storage/app`:

```bash
mkdir -p ~/backup
cp database/database.sqlite ~/backup/db-$(date +%F).sqlite
tar czf ~/backup/files-$(date +%F).tgz .env storage/app
```

Pemulihan: hentikan akses (`php artisan down`), salin file cadangan ke lokasi semula,
perbaiki permission, lalu `php artisan up`. Simpan salinan cadangan di luar server.

## 10. Log dan troubleshooting

- Log aplikasi: `storage/logs/laravel.log`. Log Nginx: `/var/log/nginx/error.log`.
- Halaman 500: periksa log aplikasi; pastikan `storage` dan `bootstrap/cache` dapat ditulis.
- Perubahan `.env` tidak berlaku: `php artisan config:clear` lalu `config:cache`.
- Email tidak terkirim: periksa `MAIL_*`, verifikasi domain pengirim, dan log aplikasi.
  Akun yang gagal dikirimi email dapat dikirimi ulang tautan dari halaman detail pengguna.
- Tautan aktivasi kedaluwarsa: gunakan fitur kirim ulang aktivasi.

## 11. Konten website dan unggah foto (dashboard admin)

Berita, galeri, fasilitas, dan struktur organisasi dikelola di dashboard
(`/admin/news`, `/admin/gallery`, `/admin/facilities`, `/admin/organization`).
Frontend membaca konten terbit lewat `GET /api/v1/content`; draf tidak pernah ikut terkirim.
Jika backend tidak terjangkau, frontend menampilkan data contoh bawaan.

Agar unggah foto berfungsi di server, lakukan sekali:

```bash
php artisan storage:link
apt install -y php8.4-gd          # untuk memperkecil foto dan membuang data GPS
printf 'upload_max_filesize = 4M\npost_max_size = 8M\n' > /etc/php/8.4/fpm/conf.d/99-tkj.ini
systemctl restart php8.4-fpm
```

Di blok `server { ... }` Nginx tambahkan `client_max_body_size 5m;` lalu `nginx -t && systemctl reload nginx`.
Tanpa pengaturan ini, foto di atas 1 MB ditolak server dengan galat 413 sebelum sampai ke aplikasi.
Batas aplikasi: JPG, PNG, atau WebP, maksimal 2 MB dan 16 megapiksel. Foto disimpan di `storage/app/public/content`,
sudah termasuk dalam cadangan di bagian 9.

Catatan privasi: nama siswa pada "Pengurus organisasi siswa" akan tampil di situs publik. Isi hanya jika ada izin.

## 12. Catatan untuk server dengan frontend terpisah dan Cloudflare Tunnel

- Debian 13 memakai PHP 8.4 (paket `php8.4-*`, soket `/run/php/php8.4-fpm.sock`). Aplikasi mendukung PHP 8.3 atau lebih baru.
- Jika port 80 dipakai layanan lain (misalnya Apache), buat Nginx mendengarkan `127.0.0.1:8081`
  dan arahkan Public Hostname Cloudflare Tunnel ke `http://localhost:8081`. HTTPS ditangani Cloudflare.
- `.env` backend: `APP_URL=https://api.jarvar.web.id`, `TRUSTED_PROXIES=*` (hanya jika Nginx tidak terbuka ke internet),
  `CORS_ALLOWED_ORIGINS=https://jarvar.web.id,https://www.jarvar.web.id`, dan `FRONTEND_URL=https://jarvar.web.id`
  agar halaman publik Laravel dialihkan ke frontend (login, dashboard, admin, aktivasi, dan API tetap aktif).
- Selama SMTP belum diatur, tautan aktivasi akun dapat dicetak dari terminal:
  `php artisan tinker --execute='$u=App\Models\User::where("username","NAMA")->firstOrFail(); $s=app(App\Services\AccountActivationService::class); echo $s->url($s->issue($u)),"\n";'`
  Tautan itu rahasia; jangan dibagikan di tempat umum.
