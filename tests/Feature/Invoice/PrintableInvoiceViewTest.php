<?php

namespace Tests\Feature\Invoice;

use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintableInvoiceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_printable_invoice_shows_the_billing_period_and_exact_hour_precision(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $user->tenants()->attach($tenant);

        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'business_name' => 'Fonts Water',
            'contact_name' => 'Jane Client',
            'contact_email' => 'billing@fontswater.test',
            'code' => 'FW',
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => 'FW20260401',
            'date' => '2026-04-01',
            'billing_period_start' => '2026-02-01',
            'billing_period_end' => '2026-03-01',
            'due_date' => '2026-04-16',
            'reference' => 'TLFEBMAR2026',
            'description' => 'Catch-up invoice',
            'amount' => 1125.00,
        ]);

        Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'date' => '2026-02-10',
            'hours' => 1.25,
            'rate' => 150.00,
            'description' => 'February project work',
            'is_billable' => true,
        ]);

        Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'date' => '2026-03-12',
            'hours' => 3.75,
            'rate' => 150.00,
            'description' => 'March project work',
            'is_billable' => true,
        ]);

        $response = $this->actingAs($user)->get(route('invoice.view', ['invoice' => $invoice, 'print' => true]));

        $response
            ->assertOk()
            ->assertSee('Billing Period:')
            ->assertSee('February – March 2026')
            ->assertSee('1.25', false)
            ->assertSee('3.75', false)
            ->assertSee('5.00', false);
    }
}
