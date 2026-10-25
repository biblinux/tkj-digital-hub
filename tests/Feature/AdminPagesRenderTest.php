<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan halaman admin dan formulir dapat dirender tanpa error Blade.
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_management_pages_render_for_admin(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeUser();

        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertOk()->assertSee('Tambah Pengguna')->assertDontSee('value="admin"', false);

        $this->actingAs($admin)->get(route('admin.users.show', $student))
            ->assertOk()->assertSee($student->email)->assertSee('Ubah');

        $this->actingAs($admin)->get(route('admin.users.edit', $student))
            ->assertOk()->assertSee('Ubah Pengguna');
    }

    public function test_super_admin_sees_admin_role_option_and_permission_editor(): void
    {
        $super = $this->makeSuperAdmin();
        $admin = $this->makeAdmin();

        $this->actingAs($super)->get(route('admin.users.create'))->assertOk()->assertSee('value="admin"', false);
        $this->actingAs($super)->get(route('admin.users.show', $admin))->assertOk()->assertSee('Izin admin')->assertSee('manage_users');
    }

    public function test_pending_user_shows_activation_state_and_resend_button(): void
    {
        $admin = $this->makeAdmin();
        $pending = $this->makeUser(User::ROLE_STUDENT, false);
        app(\App\Services\AccountActivationService::class)->issue($pending);

        $this->actingAs($admin)->get(route('admin.users.show', $pending))
            ->assertOk()->assertSee('Menunggu aktivasi')->assertSee('Kirim ulang aktivasi');
    }

    public function test_report_forms_and_detail_render(): void
    {
        $student = $this->makeUser();

        $this->actingAs($student)->get(route('reports.create'))->assertOk()->assertSee('Kategori');

        $this->actingAs($student)->post('/laporan', [
            'category' => 'lab', 'title' => 'Proyektor rusak', 'description' => 'Proyektor tidak menyala.', 'urgency' => 'normal',
        ]);

        $report = \App\Models\ItReport::firstOrFail();

        $this->actingAs($student)->get(route('reports.show', $report))
            ->assertOk()->assertSee('Proyektor rusak')->assertSee('Tambah keterangan')->assertDontSee('Tangani laporan');

        $this->actingAs($this->makeAdmin())->get(route('reports.show', $report))
            ->assertOk()->assertSee('Tangani laporan');
    }

    public function test_activation_page_and_email_render(): void
    {
        $user = $this->makeUser(User::ROLE_STUDENT, false, ['email' => 'x@example.com']);
        $token = app(\App\Services\AccountActivationService::class)->issue($user);

        $this->get(route('activation.show', $token))->assertOk()->assertSee('Aktifkan akun');

        $html = (new \App\Mail\AccountActivationMail('Budi', 'https://contoh.test/activate/abc', 60))->render();
        $this->assertStringContainsString('Halo, Budi.', $html);
        $this->assertStringContainsString('60 menit', $html);
    }
}
