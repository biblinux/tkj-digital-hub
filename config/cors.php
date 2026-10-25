<?php

/*
|--------------------------------------------------------------------------
| CORS untuk API frontend statis
|--------------------------------------------------------------------------
|
| Isi CORS_ALLOWED_ORIGINS di .env dengan origin frontend, dipisah koma,
| contoh: https://jarvar.web.id,https://www.jarvar.web.id
| Jika kosong, tidak ada origin lintas-situs yang diizinkan (aman secara bawaan).
| API memakai token Bearer, bukan cookie, jadi supports_credentials = false.
|
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept'],

    'exposed_headers' => ['Retry-After'],

    'max_age' => 600,

    'supports_credentials' => false,

];
