<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;

class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'business_name' => fake()->word(),
            'address' => fake()->text(),
            'business_phone' => fake()->word(),
            'business_email' => fake()->word(),
            'tax_id' => fake()->word(),
            'website' => fake()->word(),
            'default_rate' => fake()->randomFloat(2, 0, 99999999.99),
            'contact_name' => fake()->word(),
            'contact_title' => fake()->word(),
            'contact_email' => fake()->word(),
            'contact_phone' => fake()->word(),
            'send_invoices_to_contact' => fake()->boolean(),
            'payment_terms_days' => fake()->numberBetween(-10000, 10000),
            'invoice_notes' => fake()->text(),
            'internal_notes' => fake()->text(),
            'code' => fake()->regexify('[A-Za-z0-9]{10}'),
        ];
    }
}
