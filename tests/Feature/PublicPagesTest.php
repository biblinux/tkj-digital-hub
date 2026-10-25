<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        foreach (['/', '/profil', '/kompetensi', '/kepengurusan', '/tim-it', '/topologi', '/galeri', '/fasilitas', '/kontak', '/saran', '/login'] as $path) {
            $this->get($path)->assertOk()->assertSee('TKJ Digital Hub');
        }
    }

    public function test_unfilled_content_shows_clear_placeholder_not_invented_data(): void
    {
        $this->get('/kepengurusan')->assertSee('belum diisi');
        $this->get('/tim-it')->assertSee('belum diisi');
        $this->get('/galeri')->assertSee('belum');
    }

    public function test_configured_content_is_shown_and_escaped(): void
    {
        config(['tkj.it_team' => [['name' => '<b>Contoh</b>', 'role' => 'Teknisi']]]);

        $this->get('/tim-it')->assertSee('&lt;b&gt;Contoh&lt;/b&gt;', false);
    }

    public function test_unsafe_map_url_is_not_linked(): void
    {
        config(['tkj.school.name' => 'SMK Contoh', 'tkj.school.map_url' => 'javascript:alert(1)']);

        $this->get('/kontak')->assertDontSee('javascript:', false);
    }

    public function test_custom_error_pages(): void
    {
        $this->get('/tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan');

        $this->actingAs($this->makeUser())->get('/admin/users')->assertForbidden()->assertSee('Akses ditolak');
    }

    public function test_server_error_page_does_not_leak_details(): void
    {
        config(['app.debug' => false]);
        \Illuminate\Support\Facades\Route::get('/_uji-error', fn () => throw new \RuntimeException('rahasia-db'));

        $this->get('/_uji-error')->assertStatus(500)
            ->assertSee('Terjadi kesalahan')->assertDontSee('rahasia-db');
    }

    public function test_dashboard_is_role_aware(): void
    {
        $student = $this->makeUser();
        $this->actingAs($student)->get('/dashboard')->assertOk()
            ->assertSee('Laporan saya')->assertDontSee('Kelola pengguna')->assertDontSee('Ringkasan laporan IT');

        $tech = $this->makeUser(User::ROLE_TECHNICIAN);
        $this->actingAs($tech)->get('/dashboard')->assertOk()
            ->assertSee('Tugas untuk Anda')->assertDontSee('Kelola pengguna');

        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('Ringkasan laporan IT')->assertSee('Kelola pengguna')->assertSee('Kelola saran');

        $super = $this->makeSuperAdmin();
        $this->actingAs($super)->get('/dashboard')->assertOk()->assertSee('Super Admin');
    }

    public function test_privilege_is_not_derived_from_username_or_email(): void
    {
        $sneaky = $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'admin', 'email' => 'admin@example.com']);

        $this->actingAs($sneaky)->get('/admin/users')->assertForbidden();
    }
}
