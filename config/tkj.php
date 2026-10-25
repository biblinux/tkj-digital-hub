<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi dan konten TKJ Digital Hub
|--------------------------------------------------------------------------
|
| Nilai bernada "null" atau daftar kosong berarti informasi tersebut belum
| diisi. Halaman publik menampilkan pemberitahuan yang jelas untuk data
| yang belum tersedia, bukan data karangan. Isi data resmi sekolah di sini
| (atau lewat environment variable) sesuai dokumen sekolah.
|
*/

return [

    'name' => 'TKJ Digital Hub',

    'tagline' => 'Pusat informasi dan layanan Teknik Komputer dan Jaringan',

    // Masa berlaku tautan aktivasi akun (menit).
    'activation_ttl_minutes' => (int) env('ACTIVATION_TTL_MINUTES', 60),

    // Identitas sekolah. Isi lewat .env; kosong = ditampilkan sebagai "belum diisi".
    'school' => [
        'name' => env('SCHOOL_NAME'),
        'address' => env('SCHOOL_ADDRESS'),
        'phone' => env('SCHOOL_PHONE'),
        'email' => env('SCHOOL_EMAIL'),
        'map_url' => env('SCHOOL_MAP_URL'),
    ],

    // Gambaran umum bidang keahlian TKJ. Sesuaikan dengan kurikulum sekolah.
    'competencies' => [
        [
            'title' => 'Jaringan komputer',
            'description' => 'Perancangan, instalasi, dan konfigurasi jaringan LAN dan WAN, termasuk pengalamatan IP dan perangkat jaringan.',
        ],
        [
            'title' => 'Administrasi sistem dan server',
            'description' => 'Instalasi dan pengelolaan sistem operasi serta layanan server.',
        ],
        [
            'title' => 'Keamanan jaringan',
            'description' => 'Dasar perlindungan jaringan, pengelolaan akses, dan praktik penggunaan perangkat yang aman.',
        ],
        [
            'title' => 'Perakitan dan perawatan perangkat',
            'description' => 'Perakitan, perawatan, dan pemecahan masalah perangkat keras komputer.',
        ],
        [
            'title' => 'Dukungan teknis (helpdesk)',
            'description' => 'Penanganan laporan masalah perangkat dan jaringan secara tertib dan terdokumentasi.',
        ],
    ],

    // Data berikut belum tersedia. Tambahkan item bila data resmi sudah ada.
    // Contoh format ada di docs/KONTEN.md.
    'activities' => [],
    'organization' => [],   // ['position' => '...', 'name' => '...']
    'it_team' => [],        // ['name' => '...', 'role' => '...']
    'network_topology' => [
        'image' => null,    // contoh: 'images/topologi.png' (di folder public/)
        'description' => null,
    ],
    'gallery' => [],        // ['image' => 'images/galeri/a.jpg', 'caption' => '...']
    'facilities' => [],     // ['name' => '...', 'description' => '...']
];
