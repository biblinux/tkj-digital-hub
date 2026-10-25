<?php

return [
    'accepted' => ':attribute harus diterima.',
    'array' => ':attribute harus berupa array.',
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'max' => [
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
        'array' => ':attribute tidak boleh memiliki lebih dari :max item.',
    ],
    'min' => [
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
        'array' => ':attribute minimal memiliki :min item.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah digunakan.',

    'password' => [
        'letters' => ':attribute harus mengandung setidaknya satu huruf.',
        'mixed' => ':attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers' => ':attribute harus mengandung setidaknya satu angka.',
        'symbols' => ':attribute harus mengandung setidaknya satu simbol.',
        'uncompromised' => ':attribute pernah bocor di kebocoran data. Pilih :attribute lain.',
    ],

    'custom' => [],

    'attributes' => [
        'name' => 'nama',
        'username' => 'username',
        'email' => 'email',
        'password' => 'kata sandi',
        'login' => 'username atau email',
        'role' => 'peran',
        'subject' => 'judul',
        'message' => 'isi saran',
        'category' => 'kategori',
        'title' => 'judul',
        'description' => 'uraian',
        'location' => 'lokasi',
        'urgency' => 'urgensi',
        'status' => 'status',
        'note' => 'catatan',
        'admin_note' => 'catatan tindak lanjut',
    ],
];
