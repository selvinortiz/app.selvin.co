<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;

class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'number' => fake()->word(),
            'date' => fake()->date(),
            'billing_period_start' => fake()->date(),
            'billing_period_end' => fake()->date(),
            'due_date' => fake()->date(),
            'reference' => fake()->word(),
            'description' => fake()->text(),
            'amount' => fake()->randomFloat(2, 0, 99999999.99),
            'sent_at' => null,
            'paid_at' => null,
        ];
    }
}
