<?php

namespace Database\Seeders;

use App\Models\Hour;
use Illuminate\Database\Seeder;

class HourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Hour::factory()->count(5)->create();
    }
}
