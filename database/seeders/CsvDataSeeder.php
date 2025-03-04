<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CsvDataSeeder extends Seeder
{
    protected $tenant;
    protected $userMap = [];
    protected $clientMap = [];
    protected $invoiceMap = [];

    /**
     * Seed the application's database with CSV data.
     */
    public function run(): void
    {
        // Create or get the first tenant
        $this->tenant = Tenant::first() ?? Tenant::create(['name' => 'Default Tenant']);

        $this->importUsers();
        $this->importClients();
        $this->importInvoices();
        $this->importTimeEntries(); // Changed order since we need invoices first
    }

    protected function importUsers(): void
    {
        $handle = fopen(database_path('csv/users.csv'), 'r');
        $headers = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);

            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'), // Set a default password
            ]);

            $this->tenant->users()->attach($user);
            $this->userMap[$row['id']] = $user->id;
        }

        fclose($handle);
    }

    protected function importClients(): void
    {
        $handle = fopen(database_path('csv/clients.csv'), 'r');
        $headers = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);

            $client = Client::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->userMap[$row['user_id']] ?? null,
                'business_name' => $row['business_name'],
                'address' => $row['address'] ?? null,
                'business_phone' => $row['business_phone'] ?? null,
                'business_email' => $row['business_email'] ?? null,
                'tax_id' => $row['tax_id'] ?? null,
                'website' => $row['website'] ?? null,
                'default_rate' => $row['default_rate'] ?? 0,
                'contact_name' => $row['contact_name'] ?? null,
                'contact_email' => $row['contact_email'] ?? null,
                'code' => $row['code'] ?? strtoupper(substr($row['business_name'], 0, 3)),
            ]);

            $this->clientMap[$row['id']] = $client->id;
        }

        fclose($handle);
    }

    protected function importTimeEntries(): void
    {
        $handle = fopen(database_path('csv/time_entries.csv'), 'r');
        $headers = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);

            Hour::create([
                'tenant_id' => $this->tenant->id,
                'client_id' => $this->clientMap[$row['client_id']] ?? null,
                'user_id' => $this->userMap[$row['user_id']] ?? null,
                'invoice_id' => isset($row['invoice_id']) ? ($this->invoiceMap[$row['invoice_id']] ?? null) : null,
                'date' => $row['date'],
                'hours' => $row['hours'],
                'rate' => $row['rate'] ?? 150.00,
                'description' => $row['description'],
                'is_billable' => $row['is_billable'] ?? true,
            ]);
        }

        fclose($handle);
    }

    protected function importInvoices(): void
    {
        $handle = fopen(database_path('csv/invoices.csv'), 'r');
        $headers = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);

            $invoice = Invoice::create([
                'tenant_id' => $this->tenant->id,
                'client_id' => $this->clientMap[$row['client_id']] ?? null,
                'user_id' => $this->userMap[$row['user_id']] ?? null,
                'number' => $row['number'],
                'date' => $row['date'],
                'due_date' => $row['due_date'],
                'reference' => $row['reference'] ?? null,
                'description' => $row['description'] ?? null,
                'amount' => $row['amount'],
                'status' => InvoiceStatus::from($row['status'] ?? 'draft'),
            ]);

            $this->invoiceMap[$row['id']] = $invoice->id;
        }

        fclose($handle);
    }
}
