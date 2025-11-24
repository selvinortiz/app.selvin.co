<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\ContractorInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContractorInvoice>
 */
class ContractorInvoiceFactory extends Factory
{
    protected $model = ContractorInvoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 year', 'now');
        $dueDate = fake()->dateTimeBetween($date, '+30 days');

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'contractor_id' => Contractor::factory(),
            'invoice_number' => 'INV-' . fake()->numerify('########'),
            'date' => $date,
            'due_date' => $dueDate,
            'amount' => fake()->randomFloat(2, 100, 10000),
            'paid_at' => fake()->optional(0.5)->dateTimeBetween($date, 'now'),
            'pdf_path' => fake()->optional()->filePath(),
            'notes' => fake()->optional()->text(),
        ];
    }
}
