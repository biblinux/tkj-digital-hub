<?php

namespace Tests\Feature;

use App\Models\Suggestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuggestionTest extends TestCase
{
    use RefreshDatabase;

    private function valid(array $o = []): array
    {
        return $o + ['subject' => 'Tambah komputer', 'message' => 'Mohon tambah komputer di lab 1.'];
    }

    public function test_public_form_is_visible(): void
    {
        $this->get('/saran')->assertOk()->assertSee('Kirim Saran');
    }

    public function test_guest_can_submit_suggestion_and_it_is_stored(): void
    {
        $this->post('/saran', $this->valid(['name' => 'Anon', 'email' => 'a@example.com']))
            ->assertRedirect('/saran')->assertSessionHas('success');

        $this->assertDatabaseHas('suggestions', ['subject' => 'Tambah komputer', 'status' => 'new', 'user_id' => null]);
    }

    public function test_logged_in_submission_records_user(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/saran', $this->valid());

        $this->assertDatabaseHas('suggestions', ['user_id' => $user->id]);
    }

    public function test_validation_is_enforced(): void
    {
        $this->post('/saran', ['subject' => '', 'message' => 'pendek', 'email' => 'x'])
            ->assertSessionHasErrors(['subject', 'message', 'email']);

        $this->assertSame(0, Suggestion::count());
    }

    public function test_client_cannot_set_status_or_handler(): void
    {
        $this->post('/saran', $this->valid(['status' => 'done', 'handled_by' => 1]));

        $this->assertDatabaseHas('suggestions', ['status' => 'new', 'handled_by' => null]);
    }

    public function test_honeypot_submissions_are_silently_dropped(): void
    {
        $this->post('/saran', $this->valid(['website' => 'http://spam.example']))
            ->assertSessionHas('success');

        $this->assertSame(0, Suggestion::count());
    }

    public function test_submissions_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/saran', $this->valid())->assertRedirect('/saran');
        }

        $this->post('/saran', $this->valid())->assertStatus(429);
    }

    public function test_html_in_suggestion_is_escaped_for_managers(): void
    {
        Suggestion::create(['subject' => '<script>alert(1)</script>', 'message' => 'Pesan cukup panjang.']);

        $this->actingAs($this->makeAdmin())->get(route('admin.suggestions.index'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_only_authorised_staff_manage_suggestions(): void
    {
        $s = Suggestion::create(['subject' => 'Judul', 'message' => 'Pesan cukup panjang.']);

        $this->get(route('admin.suggestions.index'))->assertRedirect('/login');
        $this->actingAs($this->makeUser())->get(route('admin.suggestions.index'))->assertForbidden();
        $this->actingAs($this->makeUser())->patch(route('admin.suggestions.update', $s), ['status' => 'done'])->assertForbidden();
        $this->actingAs($this->makeAdmin(['view_reports']))->get(route('admin.suggestions.index'))->assertForbidden();
    }

    public function test_admin_updates_follow_up_status(): void
    {
        $admin = $this->makeAdmin();
        $s = Suggestion::create(['subject' => 'Judul', 'message' => 'Pesan cukup panjang.']);

        $this->actingAs($admin)->get(route('admin.suggestions.index'))->assertOk()->assertSee('Judul');

        $this->actingAs($admin)->patch(route('admin.suggestions.update', $s), [
            'status' => 'in_progress', 'admin_note' => 'Sedang dianggarkan.',
        ])->assertSessionHas('success');

        $s->refresh();
        $this->assertSame('in_progress', $s->status);
        $this->assertSame($admin->id, $s->handled_by);
        $this->assertNotNull($s->handled_at);

        $this->actingAs($admin)->patch(route('admin.suggestions.update', $s), ['status' => 'bogus'])
            ->assertSessionHasErrors('status');
    }
}
