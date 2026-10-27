<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_stay_active_when_frontend_url_is_empty(): void
    {
        config(['tkj.frontend_url' => null]);

        $this->get('/')->assertOk();
        $this->get('/profil')->assertOk();
        $this->get('/saran')->assertOk();
    }

    public function test_public_pages_redirect_to_frontend_when_configured(): void
    {
        config(['tkj.frontend_url' => 'https://jarvar.web.id']);

        foreach (['/profil', '/kompetensi', '/kepengurusan', '/tim-it', '/topologi', '/galeri', '/fasilitas', '/kontak', '/saran'] as $url) {
            $this->get($url)->assertRedirect('https://jarvar.web.id/');
        }
    }

    public function test_head_requests_are_redirected_too(): void
    {
        config(['tkj.frontend_url' => 'https://jarvar.web.id']);

        $this->call('HEAD', '/profil')->assertRedirect('https://jarvar.web.id/');
    }

    public function test_redirect_target_ignores_user_input(): void
    {
        config(['tkj.frontend_url' => 'https://jarvar.web.id']);

        $this->get('/profil?next=https://situs-jahat.example')->assertRedirect('https://jarvar.web.id/');
    }

    public function test_login_admin_activation_and_api_are_not_redirected(): void
    {
        config(['tkj.frontend_url' => 'https://jarvar.web.id']);

        $this->get('/login')->assertOk();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
        $this->getJson('/api/v1/meta')->assertOk();
    }

    public function test_form_posts_are_not_redirected(): void
    {
        config(['tkj.frontend_url' => 'https://jarvar.web.id']);

        $this->post('/saran', ['subject' => 'Tambah komputer', 'message' => 'Mohon tambah komputer di lab 1.'])
            ->assertRedirect('/saran');
        $this->assertDatabaseCount('suggestions', 1);
    }

    public function test_root_goes_to_admin_login_for_guests_and_dashboard_for_staff(): void
    {
        config(['tkj.frontend_url' => 'https://jarvar.web.id']);

        $this->get('/')->assertRedirect(route('login'));
        $this->get('/?next=https://situs-jahat.example')->assertRedirect(route('login'));

        $this->actingAs($this->makeUser(User::ROLE_ADMIN))->get('/')->assertRedirect(route('dashboard'));
    }
}
