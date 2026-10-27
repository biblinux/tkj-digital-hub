<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\ItReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return ApiToken::issueFor($user)[1];
    }

    private function auth(User $user): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenFor($user)];
    }

    private function reportPayload(array $o = []): array
    {
        return $o + [
            'category' => 'network',
            'title' => 'WiFi lab mati',
            'description' => 'Sejak pagi koneksi WiFi lab 2 tidak tersedia.',
            'location' => 'Lab 2',
            'urgency' => 'high',
        ];
    }

    // ---- Login ----

    public function test_login_returns_token_and_stores_only_its_hash(): void
    {
        $user = $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'siswa.api']);

        $res = $this->postJson('/api/v1/auth/login', ['login' => 'siswa.api', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.username', 'siswa.api')
            ->assertJsonMissingPath('user.email');

        $plain = $res->json('token');
        $this->assertSame(64, strlen($plain));
        $this->assertDatabaseHas('api_tokens', ['user_id' => $user->id, 'token_hash' => hash('sha256', $plain)]);
        $this->assertDatabaseMissing('api_tokens', ['token_hash' => $plain]);
    }

    public function test_login_by_email_works(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/v1/auth/login', ['login' => strtoupper($user->email), 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token']);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'salah'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Kredensial tidak valid atau akun belum aktif.');
    }

    public function test_inactive_account_gets_same_message_and_no_token(): void
    {
        $user = $this->makeUser(User::ROLE_STUDENT, false);

        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Kredensial tidak valid atau akun belum aktif.');

        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_login_is_rate_limited_after_failures(): void
    {
        RateLimiter::clear('x');
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'salah'])
                ->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])
            ->assertStatus(429);
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/v1/auth/login', [])->assertStatus(422)->assertJsonValidationErrors(['login', 'password']);
    }

    // ---- Token ----

    public function test_protected_routes_reject_missing_or_bad_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
        $this->getJson('/api/v1/reports')->assertStatus(401);
        $this->postJson('/api/v1/reports', $this->reportPayload())->assertStatus(401);
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.str_repeat('a', 64)])->assertStatus(401);
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer pendek'])->assertStatus(401);
    }

    public function test_me_returns_current_user(): void
    {
        $user = $this->makeUser();

        $this->getJson('/api/v1/me', $this->auth($user))
            ->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonPath('user.role', 'student');
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->makeUser();
        [$token, $plain] = ApiToken::issueFor($user);
        $token->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$plain])->assertStatus(401);
    }

    public function test_token_stops_working_when_account_is_deactivated(): void
    {
        $user = $this->makeUser();
        $headers = $this->auth($user);

        $user->is_active = false;
        $user->save();

        $this->getJson('/api/v1/me', $headers)->assertStatus(401);
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = $this->makeUser();
        $headers = $this->auth($user);

        $this->postJson('/api/v1/auth/logout', [], $headers)->assertOk();
        $this->assertDatabaseCount('api_tokens', 0);
        $this->getJson('/api/v1/me', $headers)->assertStatus(401);
    }

    // ---- Saran ----

    public function test_public_suggestion_is_stored(): void
    {
        $this->postJson('/api/v1/suggestions', [
            'name' => 'Anon', 'subject' => 'Tambah komputer', 'message' => 'Mohon tambah komputer di lab 1.',
        ])->assertCreated();

        $this->assertDatabaseHas('suggestions', ['subject' => 'Tambah komputer', 'status' => 'new', 'user_id' => null]);
    }

    public function test_suggestion_validation_errors(): void
    {
        $this->postJson('/api/v1/suggestions', ['subject' => 'x', 'message' => 'pendek', 'email' => 'bukan-email'])
            ->assertStatus(422)->assertJsonValidationErrors(['subject', 'message', 'email']);
    }

    public function test_suggestion_honeypot_is_not_stored(): void
    {
        $this->postJson('/api/v1/suggestions', [
            'subject' => 'Spam spam', 'message' => 'Pesan spam dari bot otomatis.', 'website' => 'http://spam.example',
        ])->assertCreated();

        $this->assertDatabaseCount('suggestions', 0);
    }

    public function test_suggestion_is_throttled(): void
    {
        $body = ['subject' => 'Tambah komputer', 'message' => 'Mohon tambah komputer di lab 1.'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/suggestions', $body)->assertCreated();
        }

        $this->postJson('/api/v1/suggestions', $body)->assertStatus(429);
    }

    // ---- Laporan ----

    public function test_student_creates_report_with_reference_and_history(): void
    {
        $user = $this->makeUser();

        $res = $this->postJson('/api/v1/reports', $this->reportPayload(), $this->auth($user))
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.updates.0.note', 'Laporan dibuat.');

        $this->assertMatchesRegularExpression('/^TKJ-\d{4}-\d{5}$/', $res->json('data.reference'));
        $this->assertDatabaseHas('it_reports', ['user_id' => $user->id, 'title' => 'WiFi lab mati']);
    }

    public function test_mass_assignment_of_status_and_owner_is_ignored(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $this->postJson('/api/v1/reports', $this->reportPayload([
            'status' => 'closed', 'user_id' => $other->id, 'assigned_to' => $other->id,
        ]), $this->auth($user))->assertCreated();

        $this->assertDatabaseHas('it_reports', ['user_id' => $user->id, 'status' => 'open', 'assigned_to' => null]);
    }

    public function test_report_validation_errors(): void
    {
        $this->postJson('/api/v1/reports', ['category' => 'ngawur', 'title' => 'a'], $this->auth($this->makeUser()))
            ->assertStatus(422)->assertJsonValidationErrors(['category', 'title', 'description', 'urgency']);
    }

    public function test_index_lists_only_own_reports(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();

        $this->postJson('/api/v1/reports', $this->reportPayload(['title' => 'Laporan milik A']), $this->auth($a));
        $this->postJson('/api/v1/reports', $this->reportPayload(['title' => 'Laporan milik B']), $this->auth($b));

        $this->getJson('/api/v1/reports', $this->auth($a))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Laporan milik A');
    }

    public function test_user_cannot_read_or_comment_on_others_report(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $id = $this->postJson('/api/v1/reports', $this->reportPayload(), $this->auth($owner))->json('data.id');

        $this->getJson("/api/v1/reports/{$id}", $this->auth($intruder))->assertNotFound();
        $this->postJson("/api/v1/reports/{$id}/comments", ['note' => 'Coba isi'], $this->auth($intruder))->assertNotFound();
        $this->assertDatabaseCount('it_report_updates', 1);
    }

    public function test_owner_sees_report_but_not_internal_notes(): void
    {
        $owner = $this->makeUser();
        $id = $this->postJson('/api/v1/reports', $this->reportPayload(), $this->auth($owner))->json('data.id');

        \App\Models\ItReportUpdate::create([
            'it_report_id' => $id, 'user_id' => $this->makeUser(User::ROLE_TECHNICIAN)->id,
            'note' => 'Catatan rahasia teknisi', 'is_internal' => true,
        ]);
        \App\Models\ItReportUpdate::create([
            'it_report_id' => $id, 'user_id' => $this->makeUser(User::ROLE_TECHNICIAN)->id,
            'note' => 'Sedang dicek', 'is_internal' => false,
        ]);

        $res = $this->getJson("/api/v1/reports/{$id}", $this->auth($owner))->assertOk();

        $this->assertStringContainsString('Sedang dicek', $res->getContent());
        $this->assertStringNotContainsString('Catatan rahasia', $res->getContent());
        $this->assertStringNotContainsString('assigned_to', $res->getContent());
    }

    public function test_owner_can_comment_until_report_is_final(): void
    {
        $owner = $this->makeUser();
        $headers = $this->auth($owner);
        $id = $this->postJson('/api/v1/reports', $this->reportPayload(), $headers)->json('data.id');

        $this->postJson("/api/v1/reports/{$id}/comments", ['note' => 'Masih mati'], $headers)->assertCreated();
        $this->assertDatabaseHas('it_report_updates', ['it_report_id' => $id, 'note' => 'Masih mati', 'is_internal' => false]);

        $this->postJson("/api/v1/reports/{$id}/comments", ['note' => ''], $headers)->assertStatus(422);

        ItReport::whereKey($id)->update(['status' => 'closed']);
        $this->postJson("/api/v1/reports/{$id}/comments", ['note' => 'Tambahan'], $headers)->assertForbidden();
    }

    // ---- Meta, CORS, bentuk respons ----

    public function test_meta_lists_form_options_publicly(): void
    {
        $this->getJson('/api/v1/meta')->assertOk()
            ->assertJsonPath('report_categories.network', 'Jaringan / internet')
            ->assertJsonStructure(['report_urgencies', 'report_statuses']);
    }

    public function test_cors_allows_only_configured_origin(): void
    {
        config(['cors.allowed_origins' => ['https://jarvar.web.id']]);

        $this->call('OPTIONS', '/api/v1/suggestions', [], [], [], [
            'HTTP_ORIGIN' => 'https://jarvar.web.id',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ])->assertHeader('Access-Control-Allow-Origin', 'https://jarvar.web.id');

        $res = $this->call('OPTIONS', '/api/v1/suggestions', [], [], [], [
            'HTTP_ORIGIN' => 'https://situs-jahat.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);
        // Origin asing tidak boleh dipantulkan; browser menolak karena header
        // tidak sama dengan origin penelepon.
        $this->assertNotSame('https://situs-jahat.example', $res->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $res->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->get('/api/v1/tidak-ada', ['Accept' => 'text/html'])
            ->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_errors_do_not_leak_internals(): void
    {
        $res = $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.str_repeat('b', 64)])->assertStatus(401);
        $this->assertStringNotContainsString('vendor/', $res->getContent());
    }

    public function test_admin_accounts_cannot_get_frontend_tokens(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN] as $i => $role) {
            $user = $this->makeUser($role, true, ['username' => 'adm'.$i]);

            $this->postJson('/api/v1/auth/login', ['login' => 'adm'.$i, 'password' => 'password'])
                ->assertForbidden()
                ->assertJsonMissingPath('token');
            $this->assertDatabaseMissing('api_tokens', ['user_id' => $user->id]);
        }
    }

    public function test_technician_can_still_use_frontend_login(): void
    {
        $this->makeUser(User::ROLE_TECHNICIAN, true, ['username' => 'tek']);

        $this->postJson('/api/v1/auth/login', ['login' => 'tek', 'password' => 'password'])->assertOk();
    }
}
