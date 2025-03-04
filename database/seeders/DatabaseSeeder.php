<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $user = User::create([
        //     'name' => 'Selvin',
        //     'email' => 'selvin@example.com',
        //     'email_verified_at' => now(),
        //     'password' => Hash::make('password'),
        // ]);

        $tenant = Tenant::create([
            'name' => 'Selvin Co',
        ]);

        // $tenant->users()->attach($user);

        // // Import CSV data
        // $this->call(CsvDataSeeder::class);

        // $client = Client::create([
        //     'tenant_id' => $tenant->id,
        //     'user_id' => $user->id,
        //     'business_name' => 'Selvin Co',
        //     'address' => '1234 Main St, Anytown, USA',
        //     'business_phone' => '1234567890',
        //     'business_email' => 'selvin@example.com',
        //     'tax_id' => '1234567890',
        //     'website' => 'https://selvin.co',
        //     'default_rate' => 150.00,
        //     'contact_name' => 'Selvin',
        //     'contact_email' => 'selvin@example.com',
        //     'code' => 'SEL',
        // ]);

        // foreach (range(1, 2) as $index) {
        //     Hour::factory()->create([
        //         'tenant_id' => $tenant->id,
        //         'client_id' => $client->id,
        //         'user_id' => $user->id,
        //     ]);
        // }

        // Invoice::create([
        //     'tenant_id' => $tenant->id,
        //     'client_id' => $client->id,
        //     'user_id' => $user->id,
        //     'number' => 'INV-001',
        //     'date' => now(),
        //     'due_date' => now()->addDays(15),
        //     'reference' => 'INV-001',
        //     'description' => 'Invoice for Selvin Co',
        //     'amount' => 1000.00,
        //     'status' => 'draft',
        // ]);

        $this->call([
            // TenantSeeder::class,
            // UserSeeder::class,
            // ClientSeeder::class,
            // InvoiceSeeder::class,
            // HourSeeder::class,
            CsvDataSeeder::class,
        ]);
    }
}
