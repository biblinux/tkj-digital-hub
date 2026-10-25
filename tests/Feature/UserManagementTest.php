<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Models\AccountActivation;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Siti Aminah',
            'username' => 'siti.aminah',
            'email' => 'siti@example.com',
            'role' => 'student',
        ];
    }

    public function test_authorized_admin_creates_inactive_user_and_sends_activation(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $this->payload());

        $user = User::where('username', 'siti.aminah')->firstOrFail();
        $response->assertRedirect(route('admin.users.show', $user));

        $this->assertFalse($user->is_active);
        $this->assertSame('student', $user->role);
        $this->assertSame(1, AccountActivation::where('user_id', $user->id)->count());
        Mail::assertSent(AccountActivationMail::class, fn ($m) => $m->hasTo('siti@example.com'));
    }

    public function test_username_and_email_are_normalised_to_lowercase(): void
    {
        Mail::fake();

        $this->actingAs($this->makeAdmin())->post(route('admin.users.store'), $this->payload([
            'username' => '  Siti.Aminah ',
            'email' => ' SITI@Example.COM ',
        ]));

        $this->assertDatabaseHas('users', ['username' => 'siti.aminah', 'email' => 'siti@example.com']);
    }

    public function test_student_cannot_access_user_management(): void
    {
        $student = $this->makeUser();

        $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.users.store'), $this->payload())->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'siti.aminah']);
    }

    public function test_admin_without_manage_users_permission_is_denied(): void
    {
        $admin = $this->makeAdmin(['view_reports']);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.store'), $this->payload())->assertForbidden();
    }

    public function test_technician_cannot_manage_users(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_TECHNICIAN))
            ->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_regular_admin_cannot_create_admin(): void
    {
        Mail::fake();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.users.store'), $this->payload(['role' => 'admin']))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'siti.aminah']);
    }

    public function test_super_admin_can_create_admin_with_default_permissions(): void
    {
        Mail::fake();

        $this->actingAs($this->makeSuperAdmin())
            ->post(route('admin.users.store'), $this->payload(['role' => 'admin']));

        $created = User::where('username', 'siti.aminah')->firstOrFail();
        $this->assertSame('admin', $created->role);
        $this->assertEqualsCanonicalizing(
            User::ADMIN_DEFAULT_PERMISSIONS,
            $created->permissions()->pluck('name')->all()
        );
    }

    public function test_super_admin_role_cannot_be_assigned_through_the_form(): void
    {
        Mail::fake();

        $this->actingAs($this->makeSuperAdmin())
            ->post(route('admin.users.store'), $this->payload(['role' => 'super_admin']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'siti.aminah']);
    }

    public function test_duplicate_username_and_email_are_rejected(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'siti.aminah', 'email' => 'siti@example.com']);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->payload())
            ->assertSessionHasErrors(['username', 'email']);

        $this->assertSame(1, User::where('username', 'siti.aminah')->count());
    }

    public function test_invalid_input_is_rejected_server_side(): void
    {
        Mail::fake();

        $this->actingAs($this->makeAdmin())->post(route('admin.users.store'), [
            'name' => '', 'username' => 'a b', 'email' => 'bukan-email', 'role' => 'hacker',
        ])->assertSessionHasErrors(['name', 'username', 'email', 'role']);
    }

    public function test_mass_assignment_cannot_set_role_or_active_flag(): void
    {
        $user = new User(['name' => 'X', 'email' => 'x@example.com', 'username' => 'x', 'password' => 'abc', 'role' => 'super_admin', 'is_active' => true]);

        $this->assertNotSame('super_admin', $user->role);
        $this->assertNotTrue($user->is_active);
    }

    public function test_email_failure_keeps_account_and_allows_resend(): void
    {
        $admin = $this->makeAdmin();

        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('smtp mati'));

        $this->actingAs($admin)->post(route('admin.users.store'), $this->payload())
            ->assertSessionHas('error');

        $user = User::where('username', 'siti.aminah')->firstOrFail();
        $this->assertFalse($user->is_active);
        $this->assertSame(1, AccountActivation::where('user_id', $user->id)->count());
    }

    public function test_resend_activation_issues_new_token_and_cancels_old_one(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $user = $this->makeUser(User::ROLE_STUDENT, false);
        $service = app(\App\Services\AccountActivationService::class);
        $old = $service->issue($user);

        $this->actingAs($admin)->post(route('admin.users.resend', $user))->assertSessionHas('success');

        Mail::assertSent(AccountActivationMail::class, 1);
        $this->get(route('activation.show', $old))->assertNotFound();
    }

    public function test_resend_is_rate_limited(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $user = $this->makeUser(User::ROLE_STUDENT, false);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin)->post(route('admin.users.resend', $user))->assertSessionHas('success');
        }

        $this->actingAs($admin)->post(route('admin.users.resend', $user))->assertSessionHas('error');
        Mail::assertSent(AccountActivationMail::class, 3);
    }

    public function test_cannot_resend_activation_for_active_account(): void
    {
        Mail::fake();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.users.resend', $this->makeUser()))
            ->assertForbidden();
    }

    public function test_listing_supports_search_filters_and_pagination(): void
    {
        $admin = $this->makeAdmin();
        $this->makeUser(User::ROLE_STUDENT, true, ['name' => 'Andi Pratama', 'display_name' => 'Andi Pratama', 'username' => 'andi']);
        $this->makeUser(User::ROLE_TECHNICIAN, true, ['name' => 'Bima Teknisi', 'display_name' => 'Bima Teknisi', 'username' => 'bima']);
        $this->makeUser(User::ROLE_STUDENT, false, ['name' => 'Citra Nonaktif', 'display_name' => 'Citra Nonaktif', 'username' => 'citra']);

        $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'andi']))
            ->assertSee('Andi Pratama')->assertDontSee('Bima Teknisi');

        $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'technician']))
            ->assertSee('Bima Teknisi')->assertDontSee('Andi Pratama');

        $this->actingAs($admin)->get(route('admin.users.index', ['status' => 'inactive']))
            ->assertSee('Citra Nonaktif')->assertDontSee('Bima Teknisi');

        $this->makeUserBatch(20);
        $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]))->assertOk();
    }

    private function makeUserBatch(int $n): void
    {
        User::factory()->student()->count($n)->create();
    }

    public function test_listing_search_treats_like_wildcards_literally(): void
    {
        $admin = $this->makeAdmin();
        $this->makeUser(User::ROLE_STUDENT, true, ['name' => 'Andi Pratama', 'display_name' => 'Andi Pratama', 'username' => 'andi']);

        $this->actingAs($admin)->get(route('admin.users.index', ['q' => '%']))
            ->assertDontSee('Andi Pratama');
    }

    public function test_admin_updates_student_and_changes_role(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'lama', 'email' => 'lama@example.com']);

        $this->actingAs($admin)->put(route('admin.users.update', $student), [
            'name' => 'Nama Baru', 'username' => 'baru', 'email' => 'baru@example.com', 'role' => 'technician',
        ])->assertRedirect(route('admin.users.show', $student));

        $student->refresh();
        $this->assertSame('baru', $student->username);
        $this->assertSame('technician', $student->role);
    }

    public function test_admin_cannot_promote_user_to_admin(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeUser();

        $this->actingAs($admin)->put(route('admin.users.update', $student), [
            'name' => $student->name, 'username' => $student->username, 'email' => $student->email, 'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertSame('student', $student->fresh()->role);
    }

    public function test_regular_admin_cannot_modify_admin_or_super_admin(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();
        $super = $this->makeSuperAdmin();

        foreach ([$other, $super] as $target) {
            $this->actingAs($admin)->get(route('admin.users.edit', $target))->assertForbidden();
            $this->actingAs($admin)->post(route('admin.users.deactivate', $target))->assertForbidden();
        }
    }

    public function test_user_cannot_edit_or_deactivate_self(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.users.deactivate', $admin))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.users.edit', $admin))->assertForbidden();
    }

    public function test_deactivation_blocks_login_and_revokes_sessions(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeUser(User::ROLE_STUDENT, true, ['username' => 'target']);

        $this->actingAs($admin)->post(route('admin.users.deactivate', $student))->assertSessionHas('success');

        $this->assertFalse($student->fresh()->is_active);

        $this->post('/logout');
        $this->post('/login', ['login' => 'target', 'password' => 'password'])->assertSessionHasErrors('login');
    }

    public function test_super_admin_cannot_deactivate_self_so_last_one_stays_active(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $only = $this->makeUser(User::ROLE_SUPER_ADMIN);

        $this->actingAs($only)->post(route('admin.users.deactivate', $only))->assertForbidden();

        $this->assertTrue($only->fresh()->is_active);
    }

    public function test_super_admin_can_deactivate_another_super_admin_while_one_remains(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $a = $this->makeUser(User::ROLE_SUPER_ADMIN);
        $b = $this->makeUser(User::ROLE_SUPER_ADMIN);

        $this->actingAs($a)->post(route('admin.users.deactivate', $b))->assertSessionHas('success');

        $this->assertTrue($a->fresh()->is_active);
        $this->assertFalse($b->fresh()->is_active);
    }

    public function test_never_activated_account_cannot_be_manually_reactivated(): void
    {
        $admin = $this->makeAdmin();
        $pending = $this->makeUser(User::ROLE_STUDENT, false);
        app(\App\Services\AccountActivationService::class)->issue($pending);

        $this->actingAs($admin)->post(route('admin.users.reactivate', $pending))->assertForbidden();
        $this->assertFalse($pending->fresh()->is_active);
    }

    public function test_previously_activated_account_can_be_reactivated(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUser(User::ROLE_STUDENT, false);
        AccountActivation::create([
            'user_id' => $user->id, 'token_hash' => hash('sha256', 'x'),
            'expires_at' => now()->subDay(), 'used_at' => now()->subDays(2),
        ]);

        $this->actingAs($admin)->post(route('admin.users.reactivate', $user))->assertSessionHas('success');
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_only_super_admin_manages_admin_permissions(): void
    {
        $super = $this->makeSuperAdmin();
        $admin = $this->makeAdmin(['manage_users']);
        $target = $this->makeAdmin(['manage_users']);

        $this->actingAs($admin)->put(route('admin.users.permissions', $target), ['permissions' => ['manage_settings']])
            ->assertForbidden();

        $this->actingAs($super)->put(route('admin.users.permissions', $target), ['permissions' => ['manage_settings', 'view_reports']])
            ->assertSessionHas('success');

        $this->assertEqualsCanonicalizing(
            ['manage_settings', 'view_reports'],
            $target->fresh()->permissions()->pluck('name')->all()
        );
    }

    public function test_unknown_permission_name_is_rejected(): void
    {
        $super = $this->makeSuperAdmin();
        $target = $this->makeAdmin();

        $this->actingAs($super)->put(route('admin.users.permissions', $target), ['permissions' => ['hapus_semua']])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_permission_seeder_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $count = Permission::count();
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->assertSame($count, Permission::count());
    }
}
