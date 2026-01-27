<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\RateCard;
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
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => $this->generateEmployeeId(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'avatar' => null,
            'supervisor_id' => null,
            'coverage_states' => null,
            'skill_tags' => null,
            'default_rate_card_id' => null,
            'address' => fake()->address(),
            'bank_name' => fake()->randomElement(['Maybank', 'CIMB', 'Public Bank', 'RHB', 'Hong Leong Bank']),
            'bank_account_no' => fake()->numerify('##########'),
            'bank_account_name' => fake()->name(),
            'status' => 'active',
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the user is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name() . ' (Admin)',
        ])->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }

    /**
     * Create a supervisor user.
     */
    public function supervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name() . ' (Supervisor)',
            'coverage_states' => $this->generateCoverageStates(),
        ])->afterCreating(function (User $user) {
            $user->assignRole('supervisor');
        });
    }

    /**
     * Create a technician user.
     */
    public function technician(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name() . ' (Technician)',
            'coverage_states' => $this->generateCoverageStates(1, 2),
            'skill_tags' => $this->generateSkillTags(),
            'default_rate_card_id' => RateCard::active()->inRandomOrder()->first()?->id,
        ])->afterCreating(function (User $user) {
            $user->assignRole('technician');
        });
    }

    /**
     * Create an independent technician (no supervisor).
     */
    public function independentTechnician(): static
    {
        return $this->technician()->state(fn (array $attributes) => [
            'supervisor_id' => null,
        ]);
    }

    /**
     * Create a technician with a supervisor.
     */
    public function technicianWithSupervisor(?int $supervisorId = null): static
    {
        return $this->technician()->state(fn (array $attributes) => [
            'supervisor_id' => $supervisorId ?? User::supervisors()->inRandomOrder()->first()?->id,
        ]);
    }

    /**
     * Set specific supervisor.
     */
    public function withSupervisor(int $supervisorId): static
    {
        return $this->state(fn (array $attributes) => [
            'supervisor_id' => $supervisorId,
        ]);
    }

    /**
     * Set coverage states.
     */
    public function withCoverageStates(array $states): static
    {
        return $this->state(fn (array $attributes) => [
            'coverage_states' => $states,
        ]);
    }

    /**
     * Set skill tags.
     */
    public function withSkillTags(array $skills): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_tags' => $skills,
        ]);
    }

    /**
     * Set rate card.
     */
    public function withRateCard(int $rateCardId): static
    {
        return $this->state(fn (array $attributes) => [
            'default_rate_card_id' => $rateCardId,
        ]);
    }

    /**
     * User with verified email.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }

    /**
     * User with recent login.
     */
    public function withRecentLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_login_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
        ]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Generate unique employee ID.
     */
    protected function generateEmployeeId(): string
    {
        $year = date('y');
        $random = fake()->unique()->numerify('####');
        return "EMP{$year}{$random}";
    }

    /**
     * Generate random coverage states.
     */
    protected function generateCoverageStates(int $min = 1, int $max = 3): array
    {
        $states = [
            'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan',
            'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah',
            'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Putrajaya'
        ];

        $count = fake()->numberBetween($min, $max);
        return fake()->randomElements($states, $count);
    }

    /**
     * Generate random skill tags.
     */
    protected function generateSkillTags(): array
    {
        $skills = [
            'Terminal Installation',
            'Terminal Repair',
            'Network Configuration',
            'Router Setup',
            'SIM Card Setup',
            'Troubleshooting',
            'Hardware Replacement',
            'Software Update',
            'Customer Training',
            'Site Survey'
        ];

        $count = fake()->numberBetween(2, 5);
        return fake()->randomElements($skills, $count);
    }
}