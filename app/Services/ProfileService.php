<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Perubahan profil oleh pemilik akun. Nama resmi (name) dan username hanya
 * diubah admin; pemilik akun mengubah nama tampilan, email, dan foto.
 */
class ProfileService
{
    public function __construct(private ContentImageService $images) {}

    /**
     * @param  array{display_name?: ?string, email: string, bio?: ?string, class_name?: ?string, show_in_directory?: bool}  $data
     */
    public function updateDetails(User $user, array $data): void
    {
        $displayName = $data['display_name'] ?? null;
        $user->display_name = ($displayName !== null && trim($displayName) !== '') ? trim($displayName) : null;
        $user->email = strtolower(trim($data['email']));

        // Kolom opsional hanya diubah bila memang dikirim, agar klien lama tidak menimpanya.
        if (array_key_exists('bio', $data)) {
            $user->bio = $this->clean($data['bio']);
        }
        if (array_key_exists('class_name', $data)) {
            $user->class_name = $this->clean($data['class_name']);
        }
        if (array_key_exists('show_in_directory', $data)) {
            $user->show_in_directory = (bool) $data['show_in_directory'];
        }

        $user->save();
    }

    private function clean(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    public function setPhoto(User $user, UploadedFile $file): void
    {
        $old = $user->photo_path;
        $user->photo_path = $this->images->store($file, 'avatars');
        $user->save();
        $this->images->delete($old);
    }

    public function removePhoto(User $user): void
    {
        $old = $user->photo_path;
        $user->photo_path = null;
        $user->save();
        $this->images->delete($old);
    }
}
