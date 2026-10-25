<?php

namespace Tests;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeUser(string $role = User::ROLE_STUDENT, bool $active = true, array $attributes = []): User
    {
        $factory = User::factory();

        $factory = match ($role) {
            User::ROLE_TECHNICIAN => $factory->technician(),
            User::ROLE_ADMIN => $factory->admin(),
            User::ROLE_SUPER_ADMIN => $factory->superAdmin(),
            default => $factory->student(),
        };

        if (! $active) {
            $factory = $factory->inactive();
        }

        return $factory->create($attributes);
    }

    /**
     * Admin dengan izin tertentu (default: izin awal admin).
     */
    protected function makeAdmin(?array $permissions = null): User
    {
        $this->seed(PermissionSeeder::class);

        $admin = $this->makeUser(User::ROLE_ADMIN);
        $names = $permissions ?? User::ADMIN_DEFAULT_PERMISSIONS;
        $admin->permissions()->sync(Permission::whereIn('name', $names)->pluck('id'));

        return $admin->fresh();
    }

    protected function makeSuperAdmin(): User
    {
        $this->seed(PermissionSeeder::class);

        return $this->makeUser(User::ROLE_SUPER_ADMIN);
    }
}
