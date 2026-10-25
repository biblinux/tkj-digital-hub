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
