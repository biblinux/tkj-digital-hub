<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Services\ContentImageService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['profile' => $this->payload($request->user())]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $this->profiles->updateDetails($request->user(), $request->input('display_name'), $request->input('email'));

        return response()->json([
            'message' => 'Profil diperbarui.',
            'profile' => $this->payload($request->user()->fresh()),
        ]);
    }

    public function photo(Request $request): JsonResponse
    {
        $request->validate(['photo' => ContentImageService::rules(true)]);

        $this->profiles->setPhoto($request->user(), $request->file('photo'));

        return response()->json([
            'message' => 'Foto profil diperbarui.',
            'profile' => $this->payload($request->user()->fresh()),
        ]);
    }

    public function removePhoto(Request $request): JsonResponse
    {
        $this->profiles->removePhoto($request->user());

        return response()->json([
            'message' => 'Foto profil dihapus.',
            'profile' => $this->payload($request->user()->fresh()),
        ]);
    }

    private function payload(User $user): array
    {
        return [
            'name' => $user->name,
            'display_name' => $user->display_name,
            'shown_name' => $user->shownName(),
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
            'photo_url' => $user->photoUrl(),
        ];
    }
}
