<?php

namespace Database\Factories;

use App\Enums\UserStatusEnum;
use App\Support\IdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => IdGenerator::generate('user'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'status' => UserStatusEnum::ACTIVE,
            'level' => 'ritel',
            'referral_code' => strtoupper(Str::random(8)),
        ];
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatusEnum::INACTIVE,
        ]);
    }

    /**
     * Indicate that the user is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatusEnum::SUSPENDED,
            'suspended_at' => now(),
        ]);
    }

    /**
     * Indicate that the user is banned.
     */
    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatusEnum::BANNED,
            'banned_at' => now(),
        ]);
    }
}
