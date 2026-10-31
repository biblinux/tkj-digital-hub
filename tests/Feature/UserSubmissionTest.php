<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\GalleryItem;
use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function h(User $u): array
    {
        return ['Authorization' => 'Bearer '.ApiToken::issueFor($u)[1], 'Accept' => 'application/json'];
    }

    private function news(array $o = []): array
    {
        return $o + ['title' => 'Praktik Jaringan LAN', 'category' => 'Kegiatan', 'summary' => 'Siswa praktik memasang jaringan LAN di lab.', 'body' => 'Isi berita.'];
    }

    public function test_submissions_require_login(): void
    {
        $this->postJson('/api/v1/submissions/news', $this->news())->assertUnauthorized();
        $this->postJson('/api/v1/submissions/gallery', [])->assertUnauthorized();
    }

    public function test_submitted_news_is_pending_and_not_public_until_approved(): void
    {
        $u = $this->makeUser(User::ROLE_STUDENT, true, ['name' => 'Rina Ayu', 'display_name' => 'Rina Ayu']);

        $this->postJson('/api/v1/submissions/news', $this->news(['status' => 'published', 'review_status' => 'approved']), $this->h($u))->assertCreated();

        $post = NewsPost::firstOrFail();
        $this->assertSame('pending', $post->review_status);
        $this->assertSame('draft', $post->status);
        $this->getJson('/api/v1/content')->assertJsonCount(0, 'news');

        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post("/admin/submissions/news/{$post->id}/review", ['decision' => 'approved'])->assertRedirect();

        $this->getJson('/api/v1/content')->assertJsonCount(1, 'news')
            ->assertJsonPath('news.0.title', 'Praktik Jaringan LAN')->assertJsonPath('news.0.author', 'Rina Ayu');

        $this->actingAs($admin)->post("/admin/submissions/news/{$post->id}/review", ['decision' => 'rejected', 'review_note' => 'Kurang lengkap'])->assertRedirect();
        $this->getJson('/api/v1/content')->assertJsonCount(0, 'news');
    }

    public function test_gallery_requires_image_and_goes_through_approval(): void
    {
        Storage::fake('public');
        $u = $this->makeUser();

        $this->postJson('/api/v1/submissions/gallery', ['caption' => 'Praktik'], $this->h($u))->assertUnprocessable();
        $this->post('/api/v1/submissions/gallery', ['caption' => 'Praktik di lab', 'image' => UploadedFile::fake()->image('a.png', 300, 200)], $this->h($u))->assertCreated();

        $item = GalleryItem::firstOrFail();
        $this->assertSame('pending', $item->review_status);
        $this->assertFalse($item->is_published);
        $this->getJson('/api/v1/content')->assertJsonCount(0, 'gallery');

        $this->actingAs($this->makeAdmin())->post("/admin/submissions/gallery/{$item->id}/review", ['decision' => 'approved'])->assertRedirect();
        $this->getJson('/api/v1/content')->assertJsonCount(1, 'gallery');
    }

    public function test_user_sees_and_deletes_only_own_submissions(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'lain', 'email' => 'lain@example.com']);
        $this->postJson('/api/v1/submissions/news', $this->news(), $this->h($a))->assertCreated();
        $id = NewsPost::firstOrFail()->id;

        $this->getJson('/api/v1/submissions', $this->h($b))->assertJsonCount(0, 'news');
        $this->deleteJson("/api/v1/submissions/news/{$id}", [], $this->h($b))->assertNotFound();
        $this->getJson('/api/v1/submissions', $this->h($a))->assertJsonCount(1, 'news');
        $this->deleteJson("/api/v1/submissions/news/{$id}", [], $this->h($a))->assertOk();
        $this->assertDatabaseCount('news_posts', 0);
    }

    public function test_admin_created_content_is_unaffected_and_pending_hidden_from_admin_lists(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/admin/news', ['title' => 'Berita Admin Resmi', 'category' => 'Informasi', 'summary' => 'Ringkasan berita admin resmi.', 'status' => 'published', 'published_at' => now()->toDateString()]);
        $this->getJson('/api/v1/content')->assertJsonCount(1, 'news');

        $u = $this->makeUser();
        $this->postJson('/api/v1/submissions/news', $this->news(), $this->h($u))->assertCreated();
        $this->actingAs($admin)->get('/admin/news')->assertOk()->assertDontSee('Praktik Jaringan LAN');
        $this->actingAs($admin)->get('/admin/submissions')->assertOk()->assertSee('Praktik Jaringan LAN');
    }

    public function test_student_cannot_open_approval_page_or_approve(): void
    {
        $u = $this->makeUser();
        $this->postJson('/api/v1/submissions/news', $this->news(), $this->h($u))->assertCreated();
        $id = NewsPost::firstOrFail()->id;

        $this->actingAs($u)->get('/admin/submissions')->assertForbidden();
        $this->actingAs($u)->post("/admin/submissions/news/{$id}/review", ['decision' => 'approved'])->assertForbidden();
    }

    public function test_validation_rejects_bad_category_and_short_title(): void
    {
        $u = $this->makeUser();
        $this->postJson('/api/v1/submissions/news', $this->news(['category' => 'Iklan']), $this->h($u))->assertUnprocessable();
        $this->postJson('/api/v1/submissions/news', $this->news(['title' => 'abc']), $this->h($u))->assertUnprocessable();
    }
}
