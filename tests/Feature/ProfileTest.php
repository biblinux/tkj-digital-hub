<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $attrs = []): User
    {
        return $this->makeUser(User::ROLE_STUDENT, true, $attrs + [
            'username' => 'rina', 'email' => 'rina@example.com', 'password' => Hash::make('Sandi-Lama-2026!'),
        ]);
    }

    private function headers(User $u): array
    {
        return ['Authorization' => 'Bearer '.ApiToken::issueFor($u)[1]];
    }

    private function png(string $name = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 200, 200);
    }

    // ---- API ----

    public function test_profile_requires_token(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
        $this->postJson('/api/v1/profile', [])->assertUnauthorized();
    }

    public function test_show_returns_own_profile(): void
    {
        $u = $this->student();

        $this->getJson('/api/v1/profile', $this->headers($u))
            ->assertOk()
            ->assertJsonPath('profile.username', 'rina')
            ->assertJsonPath('profile.email', 'rina@example.com')
            ->assertJsonMissingPath('profile.password');
    }

    public function test_user_changes_display_name_without_password_when_email_same(): void
    {
        $u = $this->student();

        $this->postJson('/api/v1/profile', ['display_name' => 'Rina A.', 'email' => 'RINA@example.com'], $this->headers($u))
            ->assertOk()
            ->assertJsonPath('profile.shown_name', 'Rina A.');

        $this->assertSame('Rina A.', $u->fresh()->display_name);
        $this->assertSame('rina@example.com', $u->fresh()->email);
    }

    public function test_changing_email_requires_correct_current_password(): void
    {
        $u = $this->student();

        $this->postJson('/api/v1/profile', ['email' => 'baru@example.com'], $this->headers($u))
            ->assertStatus(422)->assertJsonValidationErrors('current_password');
        $this->postJson('/api/v1/profile', ['email' => 'baru@example.com', 'current_password' => 'salah'], $this->headers($u))
            ->assertStatus(422)->assertJsonValidationErrors('current_password');
        $this->assertSame('rina@example.com', $u->fresh()->email);

        $this->postJson('/api/v1/profile', ['email' => 'baru@example.com', 'current_password' => 'Sandi-Lama-2026!'], $this->headers($u))
            ->assertOk();
        $this->assertSame('baru@example.com', $u->fresh()->email);
    }

    public function test_email_must_be_valid_and_unique(): void
    {
        $u = $this->student();
        $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'lain', 'email' => 'lain@example.com']);

        $this->postJson('/api/v1/profile', ['email' => 'bukan-email', 'current_password' => 'Sandi-Lama-2026!'], $this->headers($u))
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/api/v1/profile', ['email' => 'LAIN@example.com', 'current_password' => 'Sandi-Lama-2026!'], $this->headers($u))
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_privileged_fields_cannot_be_changed_through_profile(): void
    {
        $u = $this->student();

        $this->postJson('/api/v1/profile', [
            'email' => 'rina@example.com', 'role' => 'super_admin', 'is_active' => false,
            'username' => 'hacker', 'name' => 'Nama Palsu', 'password' => 'x',
        ], $this->headers($u))->assertOk();

        $u->refresh();
        $this->assertSame(User::ROLE_STUDENT, $u->role);
        $this->assertTrue($u->is_active);
        $this->assertSame('rina', $u->username);
        $this->assertNotSame('Nama Palsu', $u->name);
        $this->assertTrue(Hash::check('Sandi-Lama-2026!', $u->password));
    }

    public function test_photo_upload_replaces_old_file_and_remove_deletes_it(): void
    {
        Storage::fake('public');
        $u = $this->student();

        $first = $this->postJson('/api/v1/profile/photo', ['photo' => $this->png()], $this->headers($u))
            ->assertOk()->json('profile.photo_url');
        $this->assertNotEmpty($first);
        $path1 = $u->fresh()->photo_path;
        $this->assertStringStartsWith('content/avatars/', $path1);
        Storage::disk('public')->assertExists($path1);

        $this->postJson('/api/v1/profile/photo', ['photo' => $this->png('b.png')], $this->headers($u))->assertOk();
        $path2 = $u->fresh()->photo_path;
        $this->assertNotSame($path1, $path2);
        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertExists($path2);

        $this->postJson('/api/v1/profile/photo/remove', [], $this->headers($u))->assertOk()->assertJsonPath('profile.photo_url', null);
        Storage::disk('public')->assertMissing($path2);
        $this->assertNull($u->fresh()->photo_path);
    }

    public function test_photo_rejects_non_images_and_oversize(): void
    {
        Storage::fake('public');
        $u = $this->student();

        $this->postJson('/api/v1/profile/photo', ['photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')], $this->headers($u))
            ->assertStatus(422)->assertJsonValidationErrors('photo');
        $this->postJson('/api/v1/profile/photo', ['photo' => UploadedFile::fake()->create('svg.svg', 10, 'image/svg+xml')], $this->headers($u))
            ->assertStatus(422)->assertJsonValidationErrors('photo');
        $this->postJson('/api/v1/profile/photo', ['photo' => UploadedFile::fake()->image('big.jpg', 100, 100)->size(6000)], $this->headers($u))
            ->assertStatus(422)->assertJsonValidationErrors('photo');
        $this->postJson('/api/v1/profile/photo', [], $this->headers($u))
            ->assertStatus(422)->assertJsonValidationErrors('photo');
        $this->assertNull($u->fresh()->photo_path);
    }

    public function test_users_only_touch_their_own_profile(): void
    {
        $a = $this->student();
        $b = $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'b', 'email' => 'b@example.com']);

        $bBefore = $b->display_name;
        $this->postJson('/api/v1/profile', ['display_name' => 'Diubah A', 'email' => 'rina@example.com'], $this->headers($a))->assertOk();

        $this->assertSame('Diubah A', $a->fresh()->display_name);
        $this->assertSame($bBefore, $b->fresh()->display_name);
    }

    // ---- Dashboard (staf) ----

    public function test_staff_account_page_and_update(): void
    {
        $tech = $this->makeUser(User::ROLE_TECHNICIAN, true, ['username' => 'tek', 'email' => 'tek@example.com', 'password' => Hash::make('Sandi-Lama-2026!')]);

        $this->actingAs($tech)->get('/akun')->assertOk()->assertSee('Akun Saya')->assertSee('tek@example.com');

        $this->actingAs($tech)->post('/akun', ['display_name' => 'Pak Tek', 'email' => 'tek@example.com'])
            ->assertRedirect(route('account.edit'));
        $this->assertSame('Pak Tek', $tech->fresh()->display_name);

        $this->actingAs($tech)->post('/akun', ['email' => 'baru@example.com'])->assertSessionHasErrors('current_password');
        $this->actingAs($tech)->post('/akun', ['email' => 'baru@example.com', 'current_password' => 'Sandi-Lama-2026!'])
            ->assertSessionHasNoErrors();
        $this->assertSame('baru@example.com', $tech->fresh()->email);
    }

    public function test_staff_can_upload_photo_in_dashboard(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/akun/foto', ['photo' => $this->png()])->assertRedirect(route('account.edit'));
        Storage::disk('public')->assertExists($admin->fresh()->photo_path);

        $this->actingAs($admin)->post('/akun/foto/hapus')->assertRedirect(route('account.edit'));
        $this->assertNull($admin->fresh()->photo_path);
    }

    public function test_account_page_requires_login(): void
    {
        $this->get('/akun')->assertRedirect('/login');
        $this->post('/akun', [])->assertRedirect('/login');
    }

    public function test_deleting_user_removes_their_photo_file(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $u = $this->student();
        $this->postJson('/api/v1/profile/photo', ['photo' => $this->png()], $this->headers($u))->assertOk();
        $path = $u->fresh()->photo_path;

        $this->actingAs($admin)->delete(route('admin.users.destroy', $u), ['confirm_username' => 'rina']);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_profile_has_its_own_rate_limit_that_allows_normal_use(): void
    {
        $u = $this->student();
        $codes = [];
        for ($i = 0; $i < 15; $i++) {
            $codes[] = $this->postJson('/api/v1/profile', ['email' => 'rina@example.com'], $this->headers($u))->status();
        }

        // Pemakaian wajar (8 simpan berturut-turut) tidak terblokir, tetapi tetap ada batas.
        $this->assertSame([200], array_unique(array_slice($codes, 0, 8)));
        $this->assertContains(429, $codes);
    }
}
