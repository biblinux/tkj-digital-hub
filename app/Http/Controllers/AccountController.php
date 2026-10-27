<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Services\ContentImageService;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman "Akun saya" di dashboard (admin dan teknisi). */
class AccountController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function edit(Request $request): View
    {
        return view('account.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->profiles->updateDetails($request->user(), $request->input('display_name'), $request->input('email'));

        return redirect()->route('account.edit')->with('success', 'Profil diperbarui.');
    }

    public function photo(Request $request): RedirectResponse
    {
        $request->validate(['photo' => ContentImageService::rules(true)]);

        $this->profiles->setPhoto($request->user(), $request->file('photo'));

        return redirect()->route('account.edit')->with('success', 'Foto profil diperbarui.');
    }

    public function removePhoto(Request $request): RedirectResponse
    {
        $this->profiles->removePhoto($request->user());

        return redirect()->route('account.edit')->with('success', 'Foto profil dihapus.');
    }
}
