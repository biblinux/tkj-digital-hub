<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountActivation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Memastikan pengguna memiliki izin mengelola akun.
     */
    private function authorizeUserManagement(Request $request): void
    {
        abort_unless(
            $request->user()
                && $request->user()->is_active
                && $request->user()->hasPermission('manage_users'),
            403,
            'Kamu tidak memiliki izin untuk mengelola pengguna.'
        );
    }

    /**
     * Menampilkan daftar pengguna.
     */
    public function index(Request $request): View
    {
        $this->authorizeUserManagement($request);

        $users = User::query()
            ->select([
                'id',
                'name',
                'username',
                'email',
                'display_name',
                'role',
                'is_active',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Menampilkan formulir tambah pengguna.
     */
    public function create(Request $request): View
    {
        $this->authorizeUserManagement($request);

        return view('admin.users.create');
    }

    /**
     * Membuat akun baru dan mengirim tautan aktivasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeUserManagement($request);

        // Normalisasi username dan email sebelum validasi.
        $request->merge([
            'username' => strtolower(
                trim((string) $request->input('username'))
            ),
            'email' => strtolower(
                trim((string) $request->input('email'))
            ),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'role' => [
                'required',
                Rule::in(['student', 'technician', 'admin']),
            ],
        ]);

        // Hanya super admin yang boleh membuat akun admin.
        if (
            $validated['role'] === 'admin'
            && $request->user()->role !== 'super_admin'
        ) {
            abort(
                403,
                'Hanya super admin yang boleh membuat akun admin.'
            );
        }

        // Token asli hanya digunakan untuk tautan email.
        // Database menyimpan hash token, bukan token aslinya.
        $rawToken = Str::random(64);

        // Buat akun dan token dalam satu transaksi database.
        $user = DB::transaction(function () use (
            $validated,
            $rawToken
        ) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'display_name' => $validated['name'],
                'email' => $validated['email'],

                // Pengguna akan menetapkan password sendiri
                // melalui halaman aktivasi.
                'password' => Hash::make(Str::random(64)),

                'role' => $validated['role'],
                'is_active' => false,
            ]);

            AccountActivation::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => now()->addMinutes(60),
            ]);

            return $user;
        });

        // Kirim email setelah transaksi database berhasil.
        try {
            Mail::send(
                'emails.account-activation',
                [
                    'name' => $user->name,
                    'activationUrl' => route(
                        'activation.show',
                        ['token' => $rawToken]
                    ),
                ],
                function ($message) use ($user) {
                    $message
                        ->to($user->email)
                        ->subject(
                            'Aktivasi Akun TKJ Digital Hub'
                        );
                }
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.users.index')
                ->with(
                    'error',
                    'Akun dibuat, tetapi email aktivasi gagal diproses. Periksa konfigurasi email sebelum mencoba kembali.'
                );
        }

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'Akun berhasil dibuat. Tautan aktivasi berlaku selama 60 menit.'
            );
    }
}
