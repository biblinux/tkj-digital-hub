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

    public function updateDetails(User $user, ?string $displayName, string $email): void
    {
        $user->display_name = ($displayName !== null && trim($displayName) !== '') ? trim($displayName) : null;
        $user->email = strtolower(trim($email));
        $user->save();
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
