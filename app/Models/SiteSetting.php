<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Teks profil jurusan yang diisi admin (visi, misi, kontak, dan sebagainya). */
class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Kunci yang boleh disimpan. */
    public const KEYS = ['vision', 'mission', 'about', 'competencies', 'address', 'phone', 'email', 'map_url'];

    /** @return array<string, string> */
    public static function allValues(): array
    {
        return static::query()->pluck('value', 'key')->map(fn ($v) => (string) $v)->all();
    }

    public static function put(string $key, ?string $value): void
    {
        if (! in_array($key, self::KEYS, true)) {
            return;
        }

        $value = $value === null ? null : trim($value);

        static::query()->updateOrCreate(['key' => $key], ['value' => $value === '' ? null : $value]);
    }

    /** Memecah teks per baris menjadi larik (baris kosong dibuang). */
    public static function lines(?string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/', (string) $text) ?: []),
            fn ($l) => $l !== ''
        ));
    }
}
