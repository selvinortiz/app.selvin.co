<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;

class HourFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Hour::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'invoice_id' => Invoice::factory(),
            'date' => fake()->date(),
            'hours' => fake()->randomFloat(2, 0, 999.99),
            'rate' => fake()->randomFloat(2, 0, 99999999.99),
            'description' => fake()->text(),
            'is_billable' => fake()->boolean(),
        ];
    }
}
