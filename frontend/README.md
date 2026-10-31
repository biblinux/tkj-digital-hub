# TKJ Digital Hub - Frontend

Situs statis (HTML, CSS, JavaScript biasa) untuk `jarvar.web.id`. Seluruh konten (berita, galeri, fasilitas,
struktur, tim IT, direktori siswa, proyek siswa) dimuat dari backend `https://api.jarvar.web.id`
dan dikelola lewat dashboard admin. Tidak ada data contoh.

## Isi folder

| Berkas | Fungsi |
|---|---|
| `index.html` | Beranda |
| `pages/` | Halaman lain (profil, berita, galeri, proyek siswa, direktori siswa, masuk, laporan IT, saran, dll.) |
| `css/style.css` | Gaya tampilan |
| `js/api.*.js` | Klien API. Alamat backend diatur di sini (`BASE`) |
| `js/content.*.js` | Memuat konten dari `/api/v1/content` |
| `js/data.*.js` | Data tetap (menu, kompetensi, kontak, visi dan misi) |
| `js/main.*.js`, `js/pages.*.js`, `js/components.*.js` | Logika halaman dan komponen bersama |
| `img/` | Logo TKJ, logo SMKN 1 Leuwimunding, ikon situs |

## Cara memasang di hosting cPanel

1. Pastikan backend sudah berjalan (lihat `../docs/DEPLOYMENT.md`) dan alamat API benar:
   buka `js/api.v41.js` dan periksa `var BASE = "https://api.jarvar.web.id";`.
2. Kompres isi folder `frontend` menjadi satu berkas zip (`index.html` harus berada di dalam zip, bukan dalam subfolder).
3. Masuk cPanel hosting, buka **File Manager**, lalu masuk ke folder `public_html`.
4. Klik **Upload**, pilih berkas zip, lalu klik kanan zip tersebut dan pilih **Extract**.
5. Pastikan `index.html` berada langsung di `public_html`.
6. Buka `https://jarvar.web.id` dan tekan Ctrl+F5.

Di backend (`.env` pada VPS), pastikan `CORS_ALLOWED_ORIGINS=https://jarvar.web.id,https://www.jarvar.web.id`
agar frontend boleh memanggil API.

## Mengisi data resmi

- **Kontak** (alamat, telepon, email): `js/data.*.js`, bagian `site.contact`. Yang kosong tidak ditampilkan.
- **Visi dan misi**: `js/data.*.js`, bagian `vision.text` dan `mission.items`. Jika kosong, bagian itu disembunyikan.
- **Berita, galeri, fasilitas, struktur, tim IT, proyek, dan siswa**: dikelola dari dashboard admin, bukan di berkas ini.

## Memperbarui tampilan

Hosting menyimpan berkas JavaScript di cache. Karena itu nama berkas JavaScript memakai nomor versi
(misalnya `pages.v41.js`). Setelah mengubah kode, naikkan nomor versi pada nama berkas dan pada semua halaman HTML
yang memanggilnya (serta parameter `?v=` pada `css/style.css`), lalu unggah ulang seluruh folder.

## Uji lokal

```bash
cd frontend
python3 -m http.server 8090
```

Buka `http://localhost:8090`. Untuk menguji dengan backend lokal, ubah `BASE` di `js/api.*.js` ke alamat backend lokal
dan tambahkan `http://localhost:8090` ke `CORS_ALLOWED_ORIGINS` backend.
