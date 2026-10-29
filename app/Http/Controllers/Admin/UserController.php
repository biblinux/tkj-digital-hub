<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ItReport;
use App\Models\Permission;
use App\Models\User;
use App\Services\AccountActivationService;
use App\Services\ContentImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private AccountActivationService $activations)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_keys(User::ROLES))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->select(['id', 'name', 'username', 'email', 'display_name', 'role', 'is_active', 'created_at'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';

                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('is_active', $status === 'active'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Akun dan token dibuat bersama dalam satu transaksi. Email baru
        // dikirim setelah keduanya tersimpan.
        $user = DB::transaction(function () use ($validated): User {
            $user = new User([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'display_name' => $validated['name'],
                'class_name' => ($validated['class_name'] ?? null) ?: null,
                // Kata sandi acak; pengguna menetapkan sendiri saat aktivasi.
                'password' => Str::random(64),
            ]);
            $user->role = $validated['role'];
            $user->is_active = false;
            $user->save();

            if ($user->isAdmin()) {
                $this->grantDefaultAdminPermissions($user);
            }

            return $user;
        });

        $rawToken = $this->activations->issue($user);

        if (! $this->activations->send($user, $rawToken)) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', 'Akun dibuat, tetapi email aktivasi gagal dikirim. '
                    .'Periksa konfigurasi email lalu gunakan tombol "Kirim ulang aktivasi".');
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Akun berhasil dibuat. Tautan aktivasi berlaku '
                .$this->activations->ttlMinutes().' menit.');
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        $user->load(['permissions', 'activation']);

        return view('admin.users.show', [
            'user' => $user,
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $user) {
            $previousRole = $user->role;

            // Nama tampilan milik pengguna tidak ditimpa, kecuali masih sama dengan nama lama.
            $followsName = ! $user->display_name || $user->display_name === $user->name;

            $user->fill([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'class_name' => ($validated['class_name'] ?? null) ?: null,
            ]);
            if ($followsName) {
                $user->display_name = $validated['name'];
            }
            if (array_key_exists('show_in_directory', $validated)) {
                $user->show_in_directory = (bool) $validated['show_in_directory'];
            }

            if (isset($validated['role'])) {
                $user->role = $validated['role'];
            }

            $user->save();

            if ($previousRole !== $user->role) {
                if ($user->isAdmin()) {
                    $this->grantDefaultAdminPermissions($user);
                } else {
                    $user->permissions()->detach();
                }

                // Peran berubah: paksa login ulang agar hak akses baru berlaku.
                $this->revokeSessions($user);
            }
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Data pengguna diperbarui.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        // Jangan sampai tidak ada super admin aktif yang tersisa.
        if ($user->isSuperAdmin()
            && User::where('role', User::ROLE_SUPER_ADMIN)->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Super admin aktif terakhir tidak dapat dinonaktifkan.');
        }

        $user->is_active = false;
        $user->save();
        $this->revokeSessions($user);

        return back()->with('success', 'Akun dinonaktifkan dan semua sesi login dihentikan.');
    }

    /**
     * Menghapus akun secara permanen. Wajib mengetik ulang username sebagai
     * konfirmasi. Akun yang memiliki laporan IT tidak dihapus karena laporan
     * harus tetap tercatat; akun tersebut cukup dinonaktifkan.
     */
    public function destroy(Request $request, User $user, ContentImageService $images): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $request->validate(['confirm_username' => ['required', 'string']]);

        if (! hash_equals(Str::lower($user->username), Str::lower(trim((string) $request->input('confirm_username'))))) {
            return back()->with('error', 'Username konfirmasi tidak cocok. Akun tidak dihapus.');
        }

        $reports = ItReport::where('user_id', $user->id)->count();
        if ($reports > 0) {
            return back()->with('error', "Akun ini memiliki {$reports} laporan IT yang harus tetap tercatat, jadi tidak dapat dihapus. Nonaktifkan akun ini sebagai gantinya.");
        }

        $name = $user->shownName();
        $projectImages = $user->projects()->whereNotNull('image_path')->pluck('image_path');

        DB::transaction(function () use ($user): void {
            $this->revokeSessions($user);
            $user->delete();
        });

        $images->delete($user->photo_path);
        $projectImages->each(fn ($path) => $images->delete($path));

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$name} dihapus permanen.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        Gate::authorize('reactivate', $user);

        $user->is_active = true;
        $user->save();

        return back()->with('success', 'Akun diaktifkan kembali.');
    }

    public function resendActivation(User $user): RedirectResponse
    {
        Gate::authorize('resendActivation', $user);

        $key = 'resend-activation:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->with('error', 'Terlalu sering mengirim ulang. Coba lagi dalam '
                .ceil(RateLimiter::availableIn($key) / 60).' menit.');
        }

        RateLimiter::hit($key, 600);

        // Token baru otomatis membatalkan token lama.
        if (! $this->activations->issueAndSend($user)) {
            return back()->with('error', 'Email aktivasi gagal dikirim. Periksa konfigurasi email.');
        }

        return back()->with('success', 'Tautan aktivasi baru dikirim. Tautan lama tidak berlaku lagi.');
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('managePermissions', $user);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $ids = Permission::whereIn('name', $validated['permissions'] ?? [])->pluck('id');
        $user->permissions()->sync($ids);

        return back()->with('success', 'Izin admin diperbarui.');
    }

    /**
     * @return array<string, string>
     */
    private function assignableRoles(): array
    {
        $allowed = app(\App\Policies\UserPolicy::class)->assignableRoles(auth()->user());

        return collect(User::ROLES)->only($allowed)->all();
    }

    private function grantDefaultAdminPermissions(User $user): void
    {
        $ids = Permission::whereIn('name', User::ADMIN_DEFAULT_PERMISSIONS)->pluck('id');
        $user->permissions()->syncWithoutDetaching($ids);
    }

    /**
     * Menghentikan semua sesi aktif pengguna (bila sesi disimpan di database).
     */
    private function revokeSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
