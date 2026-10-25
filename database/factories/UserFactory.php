<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'display_name' => $name,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    // 'role' dan 'is_active' tidak mass-assignable, jadi diatur lewat afterMaking.
    private function withRole(string $role, bool $active = true): static
    {
        return $this->afterMaking(function (User $user) use ($role, $active) {
            $user->role = $role;
            $user->is_active = $active;
        });
    }

    public function student(): static
    {
        return $this->withRole(User::ROLE_STUDENT);
    }

    public function technician(): static
    {
        return $this->withRole(User::ROLE_TECHNICIAN);
    }

    public function admin(): static
    {
        return $this->withRole(User::ROLE_ADMIN);
    }

    public function superAdmin(): static
    {
        return $this->withRole(User::ROLE_SUPER_ADMIN);
    }

    public function inactive(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->is_active = false;
        });
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
