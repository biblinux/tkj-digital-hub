# API untuk Frontend Statis

API JSON ini dipakai oleh frontend statis (jarvar.web.id) untuk berbicara dengan backend
Laravel di VPS. Semua alamat di bawah relatif terhadap `{API}`, yaitu alamat backend Anda,
misalnya `https://api.jarvar.web.id` (alamat ini contoh, sesuaikan dengan VPS Anda).

Backend dan frontend harus sama-sama HTTPS. Browser memblokir halaman HTTPS yang memanggil API HTTP.

## Konfigurasi di VPS

Tambahkan di `.env` backend, lalu `php artisan migrate --force` dan `php artisan config:cache`:

```dotenv
CORS_ALLOWED_ORIGINS=https://jarvar.web.id,https://www.jarvar.web.id
API_TOKEN_TTL_MINUTES=10080
```

Jika `CORS_ALLOWED_ORIGINS` kosong, tidak ada situs lain yang boleh memanggil API dari browser.

## Autentikasi

Login mengembalikan token. Kirim token itu di setiap permintaan yang butuh login:

```
Authorization: Bearer <token>
```

Token disimpan di database sebagai hash, berlaku 7 hari secara bawaan, dan langsung tidak
berlaku jika akun dinonaktifkan atau pengguna logout.

## Endpoint

| Metode | Path | Login | Keterangan |
|---|---|---|---|
| GET | `/api/v1/meta` | tidak | Pilihan kategori, urgensi, dan status laporan |
| POST | `/api/v1/suggestions` | tidak | Kirim saran (maks. 5 per 10 menit per IP) |
| POST | `/api/v1/auth/login` | tidak | Tukar kredensial dengan token |
| POST | `/api/v1/auth/logout` | ya | Cabut token ini |
| GET | `/api/v1/me` | ya | Data pengguna saat ini |
| GET | `/api/v1/reports` | ya | Laporan milik sendiri (10 per halaman, `?page=`) |
| POST | `/api/v1/reports` | ya | Buat laporan IT |
| GET | `/api/v1/reports/{id}` | ya | Detail laporan milik sendiri |
| POST | `/api/v1/reports/{id}/comments` | ya | Tambah keterangan pada laporan sendiri |

Teknisi dan admin mengelola laporan, saran, dan pengguna lewat aplikasi Laravel (`/login`),
bukan lewat API ini.

### Login

```json
POST /api/v1/auth/login
{ "login": "username-atau-email", "password": "..." }
```

Berhasil (200): `{ "token": "...", "token_type": "Bearer", "expires_at": "...", "user": { ... } }`.
Gagal (422): `{ "message": "Kredensial tidak valid atau akun belum aktif." }`.
Terlalu banyak percobaan (429): ada `retry_after` dalam detik.

### Kirim saran

```json
POST /api/v1/suggestions
{ "name": "opsional", "email": "opsional", "subject": "min 3 huruf", "message": "min 10 huruf" }
```

Berhasil: 201. Kolom `website` adalah jebakan bot: jangan tampilkan atau isi di formulir
(sembunyikan dengan CSS dan beri `tabindex="-1"`, `autocomplete="off"`).

### Buat laporan

```json
POST /api/v1/reports
{
  "category": "network",
  "title": "WiFi lab mati",
  "description": "Sejak pagi koneksi WiFi lab 2 tidak tersedia.",
  "location": "Lab 2",
  "urgency": "high"
}
```

Nilai `category` dan `urgency` yang sah ada di `GET /api/v1/meta`. Berhasil: 201 dengan
`data.reference` seperti `TKJ-2026-00001`.

## Format error

- 401: token tidak ada, salah, kedaluwarsa, atau akun nonaktif.
- 403: tindakan tidak diizinkan (misalnya menambah keterangan pada laporan yang sudah ditutup).
- 404: laporan tidak ada atau bukan milik Anda.
- 422: validasi gagal; `{ "message": "...", "errors": { "kolom": ["..."] } }`.
- 429: terlalu banyak permintaan.

## Contoh pemakaian di frontend

```js
const API = 'https://api.jarvar.web.id'; // ganti dengan alamat backend Anda

async function api(path, { method = 'GET', body, token } = {}) {
  const res = await fetch(API + path, {
    method,
    headers: {
      'Accept': 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw Object.assign(new Error(data.message || 'Terjadi kesalahan'), { status: res.status, data });
  return data;
}

// Kirim saran
await api('/api/v1/suggestions', { method: 'POST', body: { subject: 'Tambah komputer', message: 'Mohon tambah komputer di lab 1.' } });

// Login lalu buat laporan
const { token } = await api('/api/v1/auth/login', { method: 'POST', body: { login: 'siswa', password: '...' } });
sessionStorage.setItem('tkj_token', token);
await api('/api/v1/reports', { method: 'POST', token, body: { category: 'network', title: 'WiFi lab mati', description: 'Sejak pagi tidak ada koneksi.', urgency: 'high' } });
```

Simpan token di `sessionStorage` (hilang saat tab ditutup), bukan di kode atau URL. Tampilkan semua teks dari
API dengan `textContent`, jangan `innerHTML`, supaya tidak ada celah XSS.
