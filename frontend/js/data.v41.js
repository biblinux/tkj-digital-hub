/* =====================================================================
   DATA TKJ Digital Hub
   Berita, galeri, fasilitas, struktur organisasi, tim IT, direktori siswa,
   dan proyek siswa dimuat dari backend (api.jarvar.web.id) oleh content.js.
   Bagian di bawah hanya berisi data tetap yang tidak berubah-ubah.
   ===================================================================== */
window.TKJ_DATA = {
  site: {
    name: "TKJ Digital Hub",
    school: "SMKN 1 Leuwimunding",
    dept: "Teknik Komputer dan Jaringan",
    /* Kontak resmi sekolah/jurusan. Isi bila sudah ada; yang kosong tidak ditampilkan. */
    contact: { address: "", phone: "", email: "" }
  },

  nav: [
    { label: "Beranda", href: "index.html" },
    { label: "Profil", items: [
      { label: "Profil TKJ", href: "pages/profil.html" },
      { label: "Struktur Organisasi", href: "pages/struktur-organisasi.html" },
      { label: "Tim IT", href: "pages/tim-it.html" },
      { label: "Teknisi", href: "pages/teknisi.html" },
      { label: "Tentang", href: "pages/tentang.html" }
    ]},
    { label: "Siswa", items: [
      { label: "Direktori Siswa", href: "pages/direktori-siswa.html" },
      { label: "Proyek Siswa", href: "pages/proyek-siswa.html" },
      { label: "Galeri", href: "pages/galeri.html" }
    ]},
    { label: "Berita", href: "pages/berita.html" },
    { label: "Topologi Jaringan", href: "pages/topologi-jaringan.html" },
    { label: "Layanan", items: [
      { label: "Saran dan Masukan", href: "pages/saran.html" },
      { label: "Laporan Masalah IT", href: "pages/laporan-it.html" }
    ]}
  ],

  /* Gambaran umum bidang keahlian TKJ. */
  competencies: [
    { icon: "network", title: "Jaringan komputer",
      text: "Merancang, memasang, dan memelihara jaringan LAN dan nirkabel, termasuk pengalamatan IP dan pengkabelan." },
    { icon: "server", title: "Administrasi sistem server",
      text: "Menginstal dan mengelola sistem operasi server serta layanan jaringan seperti DHCP, DNS, dan web server." },
    { icon: "shield", title: "Keamanan jaringan",
      text: "Memahami dasar pengamanan jaringan, pengaturan akses, dan praktik penggunaan jaringan yang aman." },
    { icon: "monitor", title: "Perakitan dan perawatan komputer",
      text: "Merakit, menginstal, dan merawat perangkat komputer serta menganalisis masalah perangkat keras dan lunak." }
  ],

  /* Diisi dari backend. */
  facilities: [],
  news: [],
  gallery: [],

  features: { studentDirectory: true },

  /* Visi dan misi resmi: isi teks di bawah. Bila kosong, bagian ini disembunyikan. */
  vision: { text: "" },
  mission: { items: [] },

  org: { head: null, teachers: { title: "Guru dan pembimbing", people: [] }, students: { title: "Pengurus organisasi siswa TKJ", people: [] } },

  itTeam: [],
  itServices: [
    { title: "Layanan jaringan", text: "Pemantauan dan perbaikan jaringan kabel dan Wi-Fi di lingkungan jurusan." },
    { title: "Perawatan perangkat", text: "Pemeriksaan berkala komputer laboratorium dan perangkat pendukung." },
    { title: "Pelaporan masalah", text: "Menerima laporan gangguan dan menindaklanjutinya sesuai prioritas." }
  ],

  technicians: [],
  students: [],
  projects: [],

  /* ---- Perangkat pada diagram topologi (gambaran edukatif, tanpa IP dan konfigurasi nyata) ---- */
  devices: [
    { id: "internet", name: "Internet", role: "Jaringan luar", text: "Penyedia koneksi ke luar sekolah. Pada diagram ini hanya sebagai gambaran." },
    { id: "router", name: "Router", role: "Penghubung antar jaringan", text: "Menghubungkan jaringan lokal ke internet dan mengatur jalur lalu lintas data." },
    { id: "switch", name: "Switch", role: "Pusat jaringan lokal", text: "Menghubungkan server, komputer, dan access point dalam satu jaringan lokal." },
    { id: "server", name: "Server", role: "Penyedia layanan", text: "Menjalankan layanan jaringan seperti web, DNS, dan DHCP untuk praktik." },
    { id: "pc", name: "Komputer lab", role: "Perangkat pengguna", text: "Komputer yang dipakai siswa untuk praktik di laboratorium." },
    { id: "ap", name: "Access Point", role: "Akses nirkabel", text: "Memancarkan Wi-Fi agar perangkat nirkabel dapat terhubung ke jaringan lokal." }
  ],

  /* ---- Opsi formulir ---- */
  forms: {
    suggestionCategories: ["Pembelajaran", "Fasilitas", "Kegiatan jurusan", "Website", "Lainnya"],
    problemCategories: ["Jaringan", "Komputer", "Wi-Fi", "Perangkat lain"],
    locations: ["Laboratorium komputer", "Laboratorium jaringan", "Ruang kelas", "Perpustakaan", "Area lain di sekolah"],
    priorities: ["Rendah", "Sedang", "Tinggi"]
  }
};
