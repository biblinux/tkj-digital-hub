<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryAndSiteTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $username, array $attrs = [], bool $active = true): User
    {
        return $this->makeUser(User::ROLE_STUDENT, $active, $attrs + ['username' => $username, 'email' => $username.'@example.com']);
    }

    private function content(): array
    {
        return $this->getJson('/api/v1/content')->assertOk()->json();
    }

    // ---- Direktori otomatis ----

    public function test_active_students_appear_automatically_without_private_fields(): void
    {
        $this->student('rina', ['name' => 'Rina Oktaviani', 'display_name' => 'Rina Oktaviani', 'class_name' => 'X TKJ 1', 'bio' => 'Suka jaringan.']);

        $json = $this->content();

        $this->assertCount(1, $json['students']);
        $this->assertSame(['name' => 'Rina Oktaviani', 'class_name' => 'X TKJ 1', 'bio' => 'Suka jaringan.', 'photo' => null], $json['students'][0]);
        $raw = json_encode($json['students']);
        $this->assertStringNotContainsString('rina@example.com', $raw);
        $this->assertStringNotContainsString('"username"', $raw);
    }

    public function test_inactive_and_hidden_students_are_not_listed(): void
    {
        $this->student('aktif');
        $this->student('belum', [], false);
        $this->student('sembunyi', ['show_in_directory' => false]);

        $names = collect($this->content()['students'])->pluck('name')->all();

        $this->assertCount(1, $names);
    }

    public function test_staff_and_admins_never_appear_in_student_list(): void
    {
        $this->makeUser(User::ROLE_ADMIN);
        $this->makeUser(User::ROLE_SUPER_ADMIN);
        $this->makeUser(User::ROLE_TECHNICIAN, true, ['username' => 'tek']);

        $json = $this->content();

        $this->assertCount(0, $json['students']);
        $this->assertCount(1, $json['technicians']);
    }

    public function test_activating_an_account_makes_it_appear(): void
    {
        $u = $this->student('baru', [], false);
        $this->assertCount(0, $this->content()['students']);

        $u->is_active = true;
        $u->save();

        $this->assertCount(1, $this->content()['students']);
    }

    public function test_deleting_an_account_removes_it_from_directory(): void
    {
        $admin = $this->makeAdmin();
        $u = $this->student('hapus');
        $this->assertCount(1, $this->content()['students']);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $u), ['confirm_username' => 'hapus']);

        $this->assertCount(0, $this->content()['students']);
    }

    // ---- Profil: bio, kelas, tampil di direktori ----

    public function test_student_edits_bio_class_and_can_hide_self(): void
    {
        $u = $this->student('rina');
        $h = ['Authorization' => 'Bearer '.ApiToken::issueFor($u)[1]];

        $this->postJson('/api/v1/profile', ['email' => 'rina@example.com', 'bio' => ' Halo semua ', 'class_name' => 'XI TKJ 2'], $h)
            ->assertOk()->assertJsonPath('profile.bio', 'Halo semua')->assertJsonPath('profile.class_name', 'XI TKJ 2')
            ->assertJsonPath('profile.show_in_directory', true);

        $this->postJson('/api/v1/profile', ['email' => 'rina@example.com', 'show_in_directory' => false], $h)->assertOk();
        $this->assertCount(0, $this->content()['students']);

        $this->postJson('/api/v1/profile', ['email' => 'rina@example.com', 'show_in_directory' => true], $h)->assertOk();
        $this->assertCount(1, $this->content()['students']);
    }

    public function test_omitting_optional_fields_does_not_erase_them(): void
    {
        $u = $this->student('rina', ['bio' => 'Bio lama', 'class_name' => 'X TKJ 1']);
        $h = ['Authorization' => 'Bearer '.ApiToken::issueFor($u)[1]];

        $this->postJson('/api/v1/profile', ['email' => 'rina@example.com', 'display_name' => 'Rina'], $h)->assertOk();

        $u->refresh();
        $this->assertSame('Bio lama', $u->bio);
        $this->assertSame('X TKJ 1', $u->class_name);
        $this->assertTrue($u->show_in_directory);
    }

    public function test_bio_and_class_length_limits(): void
    {
        $u = $this->student('rina');
        $h = ['Authorization' => 'Bearer '.ApiToken::issueFor($u)[1]];

        $this->postJson('/api/v1/profile', ['email' => 'rina@example.com', 'bio' => str_repeat('a', 201)], $h)
            ->assertStatus(422)->assertJsonValidationErrors('bio');
        $this->postJson('/api/v1/profile', ['email' => 'rina@example.com', 'class_name' => str_repeat('a', 31)], $h)
            ->assertStatus(422)->assertJsonValidationErrors('class_name');
    }

    public function test_bio_html_is_stored_as_text(): void
    {
        $u = $this->student('rina');
        $h = ['Authorization' => 'Bearer '.ApiToken::issueFor($u)[1]];

        $this->postJson('/api/v1/profile', ['email' => 'rina@example.com', 'bio' => '<script>alert(1)</script>'], $h)->assertOk();

        $this->assertSame('<script>alert(1)</script>', $this->content()['students'][0]['bio']);
    }

    // ---- Admin: kelas, sembunyikan, nama tampilan ----

    public function test_admin_sets_class_on_create_and_can_hide_user(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Siti Aminah', 'username' => 'siti', 'email' => 'siti@example.com', 'role' => 'student', 'class_name' => 'XII TKJ 1',
        ])->assertRedirect();

        $siti = User::where('username', 'siti')->firstOrFail();
        $this->assertSame('XII TKJ 1', $siti->class_name);

        $this->actingAs($admin)->put(route('admin.users.update', $siti), [
            'name' => 'Siti Aminah', 'username' => 'siti', 'email' => 'siti@example.com', 'role' => 'student',
            'class_name' => 'XII TKJ 1', 'show_in_directory' => '0',
        ])->assertRedirect();

        $this->assertFalse($siti->fresh()->show_in_directory);
    }

    public function test_admin_edit_does_not_overwrite_users_own_display_name(): void
    {
        $admin = $this->makeAdmin();
        $u = $this->student('rina', ['name' => 'Rina Oktaviani', 'display_name' => 'Rina Kece']);

        $this->actingAs($admin)->put(route('admin.users.update', $u), [
            'name' => 'Rina Oktaviani Putri', 'username' => 'rina', 'email' => 'rina@example.com', 'role' => 'student',
        ])->assertRedirect();

        $this->assertSame('Rina Kece', $u->fresh()->display_name);
        $this->assertSame('Rina Oktaviani Putri', $u->fresh()->name);
    }

    // ---- Profil jurusan (pengaturan situs) ----

    public function test_site_block_is_empty_until_admin_fills_it(): void
    {
        $site = $this->content()['site'];

        $this->assertNull($site['vision']);
        $this->assertSame([], $site['mission']);
        $this->assertSame([], $site['competencies']);
        $this->assertNull($site['contact']['address']);
    }

    public function test_admin_saves_site_profile_and_api_returns_it(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.site.edit'))->assertOk()->assertSee('Profil Jurusan');

        $this->actingAs($admin)->put(route('admin.site.update'), [
            'vision' => 'Menjadi jurusan unggul.',
            'mission' => "Misi satu\n\nMisi dua\n",
            'about' => 'Tentang jurusan.',
            'competencies' => "Jaringan | Memasang jaringan\nServer | Mengelola server",
            'address' => 'Jl. Contoh No. 1',
            'phone' => '0234-000',
            'email' => 'tkj@example.com',
            'map_url' => 'https://maps.example.com/x',
        ])->assertRedirect(route('admin.site.edit'));

        $site = $this->content()['site'];
        $this->assertSame('Menjadi jurusan unggul.', $site['vision']);
        $this->assertSame(['Misi satu', 'Misi dua'], $site['mission']);
        $this->assertSame([['title' => 'Jaringan', 'text' => 'Memasang jaringan'], ['title' => 'Server', 'text' => 'Mengelola server']], $site['competencies']);
        $this->assertSame('Jl. Contoh No. 1', $site['contact']['address']);
        $this->assertSame('tkj@example.com', $site['contact']['mail']);
    }

    public function test_site_settings_validation_and_blank_clears(): void
    {
        $admin = $this->makeAdmin();
        SiteSetting::put('vision', 'Lama');

        $this->actingAs($admin)->put(route('admin.site.update'), ['email' => 'bukan-email', 'map_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors(['email', 'map_url']);

        $this->actingAs($admin)->put(route('admin.site.update'), ['mission' => implode("\n", range(1, 11))])
            ->assertSessionHasErrors('mission');

        $this->actingAs($admin)->put(route('admin.site.update'), ['vision' => ''])->assertSessionHasNoErrors();
        $this->assertNull($this->content()['site']['vision']);
    }

    public function test_site_settings_require_content_permission(): void
    {
        $noContent = $this->makeAdmin([]);
        $student = $this->makeUser();

        $this->actingAs($noContent)->get(route('admin.site.edit'))->assertForbidden();
        $this->actingAs($noContent)->put(route('admin.site.update'), ['vision' => 'x'])->assertForbidden();
        $this->actingAs($student)->get(route('admin.site.edit'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.site.edit'))->assertRedirect('/login');
    }
}
