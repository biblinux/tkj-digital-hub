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

    private function headers(User $u): array
    {
        return ['Authorization' => 'Bearer '.ApiToken::issueFor($u)[1]];
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'title' => 'Jaringan Lab Sekolah',
            'description' => 'Merancang dan mengonfigurasi jaringan lab komputer sekolah.',
            'technologies' => 'MikroTik, VLAN',
            'result' => 'Jaringan stabil.',
        ];
    }

    public function test_create_requires_login(): void
    {
        $this->postJson('/api/v1/projects', $this->payload())->assertUnauthorized();
    }

    public function test_student_submits_project_pending_and_not_public(): void
    {
        $u = $this->makeUser();

        $this->postJson('/api/v1/projects', $this->payload(), $this->headers($u))
            ->assertCreated()->assertJsonPath('project.status', 'pending');

        $this->getJson('/api/v1/content')->assertOk()->assertJsonCount(0, 'projects');
    }

    public function test_auto_publish_setting_publishes_immediately(): void
    {
        config(['tkj.project_auto_publish' => true]);
        $u = $this->makeUser();

        $this->postJson('/api/v1/projects', $this->payload(), $this->headers($u))
            ->assertCreated()->assertJsonPath('project.status', 'published');

        $this->getJson('/api/v1/content')->assertJsonCount(1, 'projects');
    }

    public function test_validation_and_unsafe_link_rejected(): void
    {
        $u = $this->makeUser();

        $this->postJson('/api/v1/projects', $this->payload(['title' => 'ab']), $this->headers($u))
            ->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->postJson('/api/v1/projects', $this->payload(['link_url' => 'javascript:alert(1)']), $this->headers($u))
            ->assertUnprocessable()->assertJsonValidationErrors('link_url');
    }

    public function test_image_upload_is_stored(): void
    {
        Storage::fake('public');
        $u = $this->makeUser();

        $r = $this->post('/api/v1/projects', $this->payload(['image' => UploadedFile::fake()->image('p.png', 300, 200)]),
            $this->headers($u) + ['Accept' => 'application/json'])->assertCreated();

        $this->assertNotNull($r->json('project.image'));
        $this->assertCount(1, Storage::disk('public')->allFiles('content/projects'));
    }

    public function test_only_owner_can_edit_or_delete(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'lain', 'email' => 'lain@example.com']);
        $p = new StudentProject($this->payload());
        $p->user_id = $owner->id;
        $p->status = 'published';
        $p->save();

        $this->postJson("/api/v1/projects/{$p->id}", $this->payload(), $this->headers($other))->assertNotFound();
        $this->deleteJson("/api/v1/projects/{$p->id}", [], $this->headers($other))->assertNotFound();

        // Pemilik mengubah: kembali menunggu tinjauan.
        $this->postJson("/api/v1/projects/{$p->id}", $this->payload(['title' => 'Judul Baru Proyek']), $this->headers($owner))
            ->assertOk()->assertJsonPath('project.status', 'pending');

        $this->getJson('/api/v1/projects', $this->headers($other))->assertJsonCount(0, 'projects');
        $this->deleteJson("/api/v1/projects/{$p->id}", [], $this->headers($owner))->assertOk();
        $this->assertDatabaseMissing('student_projects', ['id' => $p->id]);
    }

    public function test_status_cannot_be_mass_assigned(): void
    {
        $u = $this->makeUser();

        $this->postJson('/api/v1/projects', $this->payload(['status' => 'published']), $this->headers($u))
            ->assertCreated()->assertJsonPath('project.status', 'pending');
    }

    public function test_admin_moderates_and_project_goes_public(): void
    {
        $author = $this->makeUser(User::ROLE_STUDENT, true, ['name' => 'Rina Ayu', 'display_name' => 'Rina Ayu', 'class_name' => 'XI TKJ 1']);
        $p = new StudentProject($this->payload());
        $p->user_id = $author->id;
        $p->save();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/admin/projects')->assertOk()->assertSee('Jaringan Lab Sekolah');
        $this->actingAs($admin)->post("/admin/projects/{$p->id}/review", ['decision' => 'published'])->assertRedirect();

        $this->getJson('/api/v1/content')
            ->assertJsonPath('projects.0.title', 'Jaringan Lab Sekolah')
            ->assertJsonPath('projects.0.author', 'Rina Ayu')
            ->assertJsonPath('projects.0.tech.1', 'VLAN');

        $this->actingAs($admin)->post("/admin/projects/{$p->id}/review", ['decision' => 'rejected', 'review_note' => 'Kurang jelas'])->assertRedirect();
        $this->getJson('/api/v1/content')->assertJsonCount(0, 'projects');
    }

    public function test_student_cannot_access_moderation(): void
    {
        $this->actingAs($this->makeUser())->get('/admin/projects')->assertForbidden();
    }

    public function test_active_student_appears_in_directory_without_private_data(): void
    {
        $this->makeUser(User::ROLE_STUDENT, true, ['name' => 'Budi Santoso', 'display_name' => 'Budi Santoso', 'class_name' => 'X TKJ 2', 'username' => 'budi', 'email' => 'budi@example.com']);
        $this->makeUser(User::ROLE_STUDENT, false, ['name' => 'Belum Aktif', 'username' => 'nonaktif', 'email' => 'n@example.com']);
        $this->makeUser(User::ROLE_STUDENT, true, ['name' => 'Sembunyi', 'username' => 'sembunyi', 'email' => 's@example.com', 'show_in_directory' => false]);
        $this->makeUser(User::ROLE_TECHNICIAN, true, ['name' => 'Teknisi', 'username' => 'tek', 'email' => 't@example.com']);

        $r = $this->getJson('/api/v1/content')->assertOk();
        $r->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.name', 'Budi Santoso')
            ->assertJsonPath('students.0.class_name', 'X TKJ 2');
        $this->assertStringNotContainsString('budi@example.com', $r->getContent());
        $this->assertStringNotContainsString('"budi"', $r->getContent());
    }

    public function test_admin_sets_class_and_student_can_edit_bio_and_opt_out(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Siti Aminah', 'username' => 'siti', 'email' => 'siti@example.com', 'role' => 'student', 'class_name' => 'XII TKJ 1',
        ]);
        $siti = User::where('username', 'siti')->firstOrFail();
        $this->assertSame('XII TKJ 1', $siti->class_name);

        // Belum aktif: belum tampil.
        $this->getJson('/api/v1/content')->assertJsonCount(0, 'students');

        $siti->is_active = true;
        $siti->save();
        $this->getJson('/api/v1/content')->assertJsonCount(1, 'students');

        $this->postJson('/api/v1/profile', ['email' => 'siti@example.com', 'bio' => 'Suka jaringan.'], $this->headers($siti))
            ->assertOk()->assertJsonPath('profile.bio', 'Suka jaringan.')->assertJsonPath('profile.class_name', 'XII TKJ 1');
        $this->getJson('/api/v1/content')->assertJsonPath('students.0.bio', 'Suka jaringan.');

        $this->postJson('/api/v1/profile', ['email' => 'siti@example.com', 'show_in_directory' => false], $this->headers($siti))->assertOk();
        $this->getJson('/api/v1/content')->assertJsonCount(0, 'students');
    }

    public function test_deleting_user_removes_projects_and_images(): void
    {
        Storage::fake('public');
        $super = $this->makeSuperAdmin();
        $u = $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'hapus', 'email' => 'hapus@example.com']);
        $this->post('/api/v1/projects', $this->payload(['image' => UploadedFile::fake()->image('p.png', 300, 200)]),
            $this->headers($u) + ['Accept' => 'application/json'])->assertCreated();

        $this->actingAs($super)->delete("/admin/users/{$u->id}", ['confirm_username' => 'hapus'])->assertRedirect();

        $this->assertDatabaseCount('student_projects', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles('content/projects'));
    }
}
