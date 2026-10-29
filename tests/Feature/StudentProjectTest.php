<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\StudentProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentProjectTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $name = 'budi'): User
    {
        return $this->makeUser(User::ROLE_STUDENT, true, ['username' => $name, 'email' => $name.'@example.com']);
    }

    private function h(User $u): array
    {
        return ['Authorization' => 'Bearer '.ApiToken::issueFor($u)[1]];
    }

    private function payload(array $o = []): array
    {
        return $o + [
            'title' => 'Server web di Debian',
            'description' => 'Membangun server web Nginx di Debian untuk laboratorium.',
            'tech' => 'Debian, Nginx, PHP',
            'result' => 'Situs uji dapat diakses dari jaringan lab.',
            'link_url' => 'https://github.com/contoh/proyek',
        ];
    }

    private function make(User $u, array $o = []): StudentProject
    {
        $p = new StudentProject(['title' => 'Proyek contoh', 'description' => 'Deskripsi proyek contoh yang cukup panjang.', 'tech' => ['A'], 'result' => null, 'link_url' => null]);
        $p->user_id = $u->id;
        $p->status = $o['status'] ?? StudentProject::PENDING;
        $p->reviewed_at = $p->status === StudentProject::PUBLISHED ? now() : null;
        $p->save();

        return $p;
    }

    // ---- API pemilik ----

    public function test_requires_token(): void
    {
        $this->getJson('/api/v1/projects/mine')->assertUnauthorized();
        $this->postJson('/api/v1/projects', $this->payload())->assertUnauthorized();
    }

    public function test_student_creates_project_as_pending(): void
    {
        $u = $this->student();

        $res = $this->postJson('/api/v1/projects', $this->payload(), $this->h($u))
            ->assertCreated()
            ->assertJsonPath('project.status', 'pending')
            ->assertJsonPath('project.tech', ['Debian', 'Nginx', 'PHP']);

        $this->assertDatabaseHas('student_projects', ['id' => $res->json('project.id'), 'user_id' => $u->id, 'status' => 'pending']);
    }

    public function test_status_and_owner_cannot_be_mass_assigned(): void
    {
        $u = $this->student();
        $other = $this->student('lain');

        $this->postJson('/api/v1/projects', $this->payload(['status' => 'published', 'user_id' => $other->id, 'reviewed_by' => $other->id]), $this->h($u))->assertCreated();

        $p = StudentProject::firstOrFail();
        $this->assertSame('pending', $p->status);
        $this->assertSame($u->id, $p->user_id);
        $this->assertNull($p->reviewed_by);
    }

    public function test_validation_rules(): void
    {
        $u = $this->student();

        $bad = [
            ['title' => 'abc'],
            ['description' => 'terlalu pendek'],
            ['link_url' => 'javascript:alert(1)'],
            ['link_url' => 'ftp://situs.example/file'],
            ['tech' => 'a,b,c,d,e,f,g,h,i'],
            ['tech' => str_repeat('x', 31)],
            ['result' => str_repeat('x', 301)],
        ];
        foreach ($bad as $o) {
            $this->postJson('/api/v1/projects', $this->payload($o), $this->h($u))->assertStatus(422);
        }
        $this->assertDatabaseCount('student_projects', 0);
    }

    public function test_html_in_text_is_stored_as_text_and_never_executed_in_api(): void
    {
        $u = $this->student();
        $this->postJson('/api/v1/projects', $this->payload(['title' => '<script>alert(1)</script> Proyek']), $this->h($u))->assertCreated();

        // Disimpan apa adanya sebagai teks; frontend dan Blade selalu meng-escape.
        $this->assertSame('<script>alert(1)</script> Proyek', StudentProject::firstOrFail()->title);
    }

    public function test_image_upload_and_replacement(): void
    {
        Storage::fake('public');
        $u = $this->student();

        $id = $this->postJson('/api/v1/projects', $this->payload(['image' => UploadedFile::fake()->image('a.png', 300, 200)]), $this->h($u))
            ->assertCreated()->json('project.id');
        $path1 = StudentProject::find($id)->image_path;
        $this->assertStringStartsWith('content/projects/', $path1);
        Storage::disk('public')->assertExists($path1);

        $this->postJson('/api/v1/projects/'.$id, $this->payload(['image' => UploadedFile::fake()->image('b.png', 300, 200)]), $this->h($u))->assertOk();
        $path2 = StudentProject::find($id)->image_path;
        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertExists($path2);

        $this->postJson('/api/v1/projects/'.$id, $this->payload(['remove_image' => true]), $this->h($u))->assertOk();
        Storage::disk('public')->assertMissing($path2);
        $this->assertNull(StudentProject::find($id)->image_path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $u = $this->student();

        $this->postJson('/api/v1/projects', $this->payload(['image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]), $this->h($u))
            ->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_editing_a_published_project_sends_it_back_to_review(): void
    {
        $u = $this->student();
        $p = $this->make($u, ['status' => StudentProject::PUBLISHED]);

        $this->postJson('/api/v1/projects/'.$p->id, $this->payload(['title' => 'Judul baru yang lebih baik']), $this->h($u))
            ->assertOk()->assertJsonPath('project.status', 'pending');

        $this->assertNull($p->fresh()->reviewed_at);
        $this->getJson('/api/v1/content')->assertJsonCount(0, 'projects');
    }

    public function test_user_cannot_touch_other_users_projects(): void
    {
        $a = $this->student('a');
        $b = $this->student('b');
        $p = $this->make($a);

        $this->postJson('/api/v1/projects/'.$p->id, $this->payload(), $this->h($b))->assertNotFound();
        $this->postJson('/api/v1/projects/'.$p->id.'/delete', [], $this->h($b))->assertNotFound();
        $this->getJson('/api/v1/projects/mine', $this->h($b))->assertJsonCount(0, 'projects');
        $this->assertDatabaseHas('student_projects', ['id' => $p->id]);
    }

    public function test_owner_deletes_own_project(): void
    {
        Storage::fake('public');
        $u = $this->student();
        $id = $this->postJson('/api/v1/projects', $this->payload(['image' => UploadedFile::fake()->image('a.png', 100, 100)]), $this->h($u))->json('project.id');
        $path = StudentProject::find($id)->image_path;

        $this->postJson('/api/v1/projects/'.$id.'/delete', [], $this->h($u))->assertOk();

        $this->assertDatabaseMissing('student_projects', ['id' => $id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_per_user_limit(): void
    {
        $u = $this->student();
        for ($i = 0; $i < StudentProject::MAX_PER_USER; $i++) {
            $this->make($u);
        }

        $this->postJson('/api/v1/projects', $this->payload(), $this->h($u))->assertStatus(422);
        $this->assertSame(StudentProject::MAX_PER_USER, StudentProject::count());
    }

    public function test_mine_shows_reject_reason_only_when_rejected(): void
    {
        $u = $this->student();
        $p = $this->make($u, ['status' => StudentProject::REJECTED]);
        $p->reject_reason = 'Deskripsi kurang jelas';
        $p->save();

        $this->getJson('/api/v1/projects/mine', $this->h($u))
            ->assertJsonPath('projects.0.status', 'rejected')
            ->assertJsonPath('projects.0.reject_reason', 'Deskripsi kurang jelas');
    }

    // ---- Konten publik ----

    public function test_public_content_lists_only_published_with_author_and_no_private_fields(): void
    {
        $u = $this->student();
        $this->make($u, ['status' => StudentProject::PENDING]);
        $this->make($u, ['status' => StudentProject::REJECTED]);
        $pub = $this->make($u, ['status' => StudentProject::PUBLISHED]);

        $res = $this->getJson('/api/v1/content')->assertOk()->assertJsonCount(1, 'projects');
        $this->assertSame($pub->id, $res->json('projects.0.id'));
        $this->assertSame($u->shownName(), $res->json('projects.0.author'));
        $res->assertJsonMissingPath('projects.0.status')
            ->assertJsonMissingPath('projects.0.user_id')
            ->assertJsonMissingPath('projects.0.reject_reason')
            ->assertJsonMissingPath('projects.0.email');
    }

    // ---- Moderasi admin ----

    public function test_admin_approves_and_rejects(): void
    {
        $admin = $this->makeAdmin();
        $u = $this->student();
        $p = $this->make($u);

        $this->actingAs($admin)->get(route('admin.projects.index'))->assertOk()->assertSee('Proyek contoh');
        $this->actingAs($admin)->get(route('admin.projects.show', $p))->assertOk()->assertSee('Setujui dan tampilkan');

        $this->actingAs($admin)->post(route('admin.projects.approve', $p))->assertRedirect(route('admin.projects.index'));
        $p->refresh();
        $this->assertSame('published', $p->status);
        $this->assertSame($admin->id, $p->reviewed_by);
        $this->getJson('/api/v1/content')->assertJsonCount(1, 'projects');

        $this->actingAs($admin)->post(route('admin.projects.reject', $p), ['reject_reason' => 'Tidak sesuai'])->assertRedirect();
        $p->refresh();
        $this->assertSame('rejected', $p->status);
        $this->assertSame('Tidak sesuai', $p->reject_reason);
        $this->getJson('/api/v1/content')->assertJsonCount(0, 'projects');
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->makeAdmin();
        $p = $this->make($this->student());

        $this->actingAs($admin)->post(route('admin.projects.reject', $p), [])->assertSessionHasErrors('reject_reason');
        $this->assertSame('pending', $p->fresh()->status);
    }

    public function test_admin_deletes_project_and_image(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $u = $this->student();
        $id = $this->postJson('/api/v1/projects', $this->payload(['image' => UploadedFile::fake()->image('a.png', 100, 100)]), $this->h($u))->json('project.id');
        $path = StudentProject::find($id)->image_path;

        $this->actingAs($admin)->delete(route('admin.projects.destroy', $id))->assertRedirect(route('admin.projects.index'));

        $this->assertDatabaseMissing('student_projects', ['id' => $id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_moderation_requires_content_permission(): void
    {
        $noPerm = $this->makeAdmin([]);
        $student = $this->student();
        $p = $this->make($student);

        foreach ([$noPerm, $student] as $actor) {
            $this->actingAs($actor)->get(route('admin.projects.index'))->assertForbidden();
            $this->actingAs($actor)->post(route('admin.projects.approve', $p))->assertForbidden();
            $this->actingAs($actor)->delete(route('admin.projects.destroy', $p))->assertForbidden();
        }
        $this->assertSame('pending', $p->fresh()->status);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $p = $this->make($this->student());
        $this->get(route('admin.projects.index'))->assertRedirect('/login');
        $this->post(route('admin.projects.approve', $p))->assertRedirect('/login');
    }

    public function test_deleting_user_removes_their_projects_and_images(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $u = $this->student();
        $id = $this->postJson('/api/v1/projects', $this->payload(['image' => UploadedFile::fake()->image('a.png', 100, 100)]), $this->h($u))->json('project.id');
        $path = StudentProject::find($id)->image_path;

        $this->actingAs($admin)->delete(route('admin.users.destroy', $u), ['confirm_username' => 'budi'])->assertSessionHas('success');

        $this->assertDatabaseMissing('student_projects', ['id' => $id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_menu_shows_pending_count_for_content_admins(): void
    {
        $admin = $this->makeAdmin();
        $this->make($this->student());

        $this->actingAs($admin)->get('/dashboard')->assertSee('Proyek Siswa (1)');
    }
}
