<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\SecureFile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SecureFileFactory extends Factory
{
    protected $model = SecureFile::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'name' => $this->faker->words(3, true),
            'filename' => $this->faker->filePath(),
            'file_path' => 'secure-files/' . $this->faker->uuid . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(1000, 5000000),
            'access_token' => Str::random(32),
            'password' => $this->faker->optional(0.3)->password(),
            'download_limit' => $this->faker->randomElement([1, 3, 5, 10]),
            'download_count' => 0,
            'expires_at' => $this->faker->optional(0.2)->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function oneTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'download_limit' => 1,
        ]);
    }

    public function passwordProtected(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => 'secret123',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}

