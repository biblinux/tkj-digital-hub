<?php

namespace App\Services;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Penyimpanan foto konten (berita, galeri, fasilitas).
 *
 * - Hanya JPG, PNG, dan WebP; SVG dan jenis lain ditolak.
 * - Nama file diacak; nama asli dari pengguna tidak dipakai.
 * - Jika ekstensi GD tersedia, foto disimpan ulang: metadata (termasuk lokasi
 *   GPS) dibuang, orientasi diperbaiki, dan sisi terpanjang dibatasi
 *   MAX_EDGE piksel. Tanpa GD, berkas disimpan apa adanya.
 */
class ContentImageService
{
    public const MAX_KB = 2048;

    public const MAX_PIXELS = 16_000_000;

    public const MAX_EDGE = 1600;

    /** Aturan validasi untuk kolom foto. */
    public static function rules(bool $required): array
    {
        return [
            'bail',
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:'.self::MAX_KB,
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }
                $size = @getimagesize($value->getRealPath());
                if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS) {
                    $fail('Resolusi foto terlalu besar (maksimal 16 megapiksel). Kecilkan foto lalu coba lagi.');
                }
            },
        ];
    }

    /** Menyimpan foto ke disk publik dan mengembalikan jalur relatifnya. */
    public function store(UploadedFile $file, string $dir): string
    {
        $dir = 'content/'.trim($dir, '/');

        if (! extension_loaded('gd')) {
            return (string) $file->store($dir, 'public');
        }

        [$data, $extension] = $this->reencode($file);
        $path = $dir.'/'.Str::random(40).'.'.$extension;
        Storage::disk('public')->put($path, $data);

        return $path;
    }

    /** Menghapus foto. Hanya jalur di bawah content/ yang boleh dihapus. */
    public function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'content/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }

    /** @return array{0: string, 1: string} [isi berkas, ekstensi] */
    private function reencode(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $image = @imagecreatefromstring((string) file_get_contents($realPath));

        if ($image === false) {
            throw ValidationException::withMessages([
                'image' => 'File gambar rusak atau tidak dapat dibaca.',
            ]);
        }

        $mime = $file->getMimeType();

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($realPath);
            $angle = match ($exif['Orientation'] ?? 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);
                if ($rotated !== false) {
                    $image = $rotated;
                }
            }
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if (max($width, $height) > self::MAX_EDGE) {
            $scale = self::MAX_EDGE / max($width, $height);
            $scaled = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
            if ($scaled !== false) {
                $image = $scaled;
            }
        }

        ob_start();
        if ($mime === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, null, 6);
            $extension = 'png';
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagewebp($image, null, 82);
            $extension = 'webp';
        } else {
            imagejpeg($image, null, 85);
            $extension = 'jpg';
        }
        $data = (string) ob_get_clean();

        return [$data, $extension];
    }
}
