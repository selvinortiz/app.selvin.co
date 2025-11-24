<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contractor>
 */
class ContractorFactory extends Factory
{
    protected $model = Contractor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'country' => fake()->country(),
            'tax_id' => fake()->numerify('########'),
            'date_of_birth' => fake()->date(),
            'payment_method' => fake()->randomElement(['ACH', 'Wire']),
            'bank_routing' => fake()->numerify('#########'),
            'bank_account' => fake()->numerify('##########'),
            'payment_terms_days' => fake()->randomElement([15, 30]),
            'notes' => fake()->optional()->text(),
        ];
    }
}
