<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = [], bool $active = true): User
    {
        return $this->makeUser(User::ROLE_TECHNICIAN, $active, $attrs + [
            'username' => 'budi',
            'email' => 'budi@example.com',
            'password' => Hash::make('Rahasia-Kuat-2026!'),
        ]);
    }

    public function test_login_with_username_succeeds(): void
    {
        $this->user();

        $this->post('/login', ['login' => 'budi', 'password' => 'Rahasia-Kuat-2026!'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_login_with_email_is_case_insensitive(): void
    {
        $this->user();

        $this->post('/login', ['login' => 'BUDI@Example.com', 'password' => 'Rahasia-Kuat-2026!'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->user();

        $this->from('/login')
            ->post('/login', ['login' => 'budi', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login_and_message_is_generic(): void
    {
        $this->user([], active: false);

        $this->from('/login')
            ->post('/login', ['login' => 'budi', 'password' => 'Rahasia-Kuat-2026!'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();

        // Pesan sama dengan kata sandi salah: status akun tidak bocor.
        $this->post('/login', ['login' => 'budi', 'password' => 'salah'])
            ->assertSessionHasErrors(['login' => 'Kredensial tidak valid atau akun belum aktif.']);
    }

    public function test_unknown_user_gets_same_error(): void
    {
        $this->post('/login', ['login' => 'tidakada', 'password' => 'x'])
            ->assertSessionHasErrors(['login' => 'Kredensial tidak valid atau akun belum aktif.']);
    }

    public function test_session_id_is_regenerated_after_login(): void
    {
        $this->user();

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['login' => 'budi', 'password' => 'Rahasia-Kuat-2026!']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_logout_ends_session(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_repeated_failures_are_rate_limited(): void
    {
        $this->user();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => 'budi', 'password' => 'salah']);
        }

        $this->post('/login', ['login' => 'budi', 'password' => 'Rahasia-Kuat-2026!'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_deactivated_user_with_open_session_is_logged_out(): void
    {
        $user = $this->user();

        $this->actingAs($user);
        $user->is_active = false;
        $user->save();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_csrf_protection_is_applied_to_login_route(): void
    {
        // Satu request memastikan kernel HTTP sudah mendaftarkan grup middleware.
        $this->get('/login')->assertOk();

        $route = app('router')->getRoutes()->getByName('login.store');

        $router = app('router');
        $middleware = collect($router->resolveMiddleware($router->gatherRouteMiddleware($route)))
            ->filter(fn ($m) => is_string($m))
            ->implode(' ');

        $this->assertMatchesRegularExpression('/Forgery|Csrf/i', $middleware);
    }

    public function test_student_cannot_use_dashboard_login(): void
    {
        $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'siswa', 'password' => Hash::make('Rahasia-Kuat-2026!')]);

        $this->post('/login', ['login' => 'siswa', 'password' => 'Rahasia-Kuat-2026!'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_admin_and_technician_can_use_dashboard_login(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN, User::ROLE_TECHNICIAN] as $i => $role) {
            $this->makeUser($role, true, ['username' => 'staf'.$i, 'password' => Hash::make('Rahasia-Kuat-2026!')]);

            $this->post('/login', ['login' => 'staf'.$i, 'password' => 'Rahasia-Kuat-2026!'])
                ->assertRedirect(route('dashboard'));
            $this->assertAuthenticated();
            $this->post('/logout');
        }
    }
}
