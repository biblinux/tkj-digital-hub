<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\ItReport;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $username = 'budi'): User
    {
        return $this->makeUser(User::ROLE_STUDENT, true, ['username' => $username]);
    }

    private function destroy(User $actor, User $target, ?string $confirm = null)
    {
        return $this->actingAs($actor)->delete(route('admin.users.destroy', $target), [
            'confirm_username' => $confirm ?? $target->username,
        ]);
    }

    public function test_admin_deletes_student_with_username_confirmation(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->student();
        ApiToken::issueFor($student);

        $this->destroy($admin, $student)
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $student->id]);
    }

    public function test_wrong_confirmation_does_not_delete(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->student();

        $this->destroy($admin, $student, 'salah')->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_missing_confirmation_does_not_delete(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->student();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $student))
            ->assertSessionHasErrors('confirm_username');

        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_user_with_reports_cannot_be_deleted(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->student();
        $r = new ItReport(['category' => 'network', 'title' => 'WiFi mati', 'description' => 'Tidak ada koneksi di lab.', 'location' => 'Lab 1', 'urgency' => 'high']);
        $r->user_id = $student->id;
        $r->status = 'open';
        $r->save();

        $this->destroy($admin, $student)->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $student->id]);
        $this->assertDatabaseCount('it_reports', 1);
    }

    public function test_suggestions_survive_when_author_is_deleted(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->student();
        $s = new Suggestion(['subject' => 'Tambah komputer', 'message' => 'Mohon tambah komputer di lab 1.']);
        $s->user_id = $student->id;
        $s->save();

        $this->destroy($admin, $student)->assertSessionHas('success');

        $this->assertDatabaseHas('suggestions', ['id' => $s->id, 'user_id' => null]);
    }

    public function test_admin_cannot_delete_self_other_admins_or_super_admins(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();
        $super = $this->makeUser(User::ROLE_SUPER_ADMIN);

        foreach ([$admin, $other, $super] as $target) {
            $this->destroy($admin, $target)->assertForbidden();
            $this->assertDatabaseHas('users', ['id' => $target->id]);
        }
    }

    public function test_super_admin_can_delete_admin_but_not_super_admin_or_self(): void
    {
        $super = $this->makeSuperAdmin();
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $otherSuper = $this->makeUser(User::ROLE_SUPER_ADMIN);

        $this->destroy($super, $super)->assertForbidden();
        $this->destroy($super, $otherSuper)->assertForbidden();
        $this->destroy($super, $admin)->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $super->id]);
        $this->assertDatabaseHas('users', ['id' => $otherSuper->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }

    public function test_admin_without_manage_users_permission_cannot_delete(): void
    {
        $admin = $this->makeAdmin([]);
        $student = $this->student();

        $this->destroy($admin, $student)->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_student_and_guest_cannot_delete(): void
    {
        $student = $this->student('a');
        $target = $this->student('b');

        $this->destroy($student, $target)->assertForbidden();
        auth()->logout();
        $this->delete(route('admin.users.destroy', $target), ['confirm_username' => 'b'])->assertRedirect('/login');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_delete_button_is_shown_only_when_allowed(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->student();
        $other = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.users.show', $student))->assertSee('Hapus akun permanen');
        $this->actingAs($admin)->get(route('admin.users.show', $other))->assertDontSee('Hapus akun permanen');
    }
}
