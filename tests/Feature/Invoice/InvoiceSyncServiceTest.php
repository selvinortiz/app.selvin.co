<?php

namespace Tests\Feature\Invoice;

use App\Exceptions\NonContiguousBillingPeriodException;
use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_invoice_amount_reference_and_billing_period_from_linked_hours(): void
    {
        [$user, $tenant, $client] = $this->makeTenantContext();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'date' => '2026-04-01',
            'billing_period_start' => '2026-04-01',
            'billing_period_end' => '2026-04-01',
            'reference' => 'TLAPR2026',
            'amount' => 0,
        ]);

        Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'date' => '2026-02-10',
            'hours' => 6.00,
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
            'hours' => 2.00,
            'rate' => 175.00,
            'description' => 'March project work',
            'is_billable' => true,
        ]);

        $invoice = InvoiceSyncService::sync($invoice);

        $this->assertSame('2026-02-01', $invoice->billing_period_start->toDateString());
        $this->assertSame('2026-03-01', $invoice->billing_period_end->toDateString());
        $this->assertSame('TLFEBMAR2026', $invoice->reference);
        $this->assertSame('1250.00', $invoice->amount);
    }

    public function test_sync_rejects_non_contiguous_linked_months(): void
    {
        [$user, $tenant, $client] = $this->makeTenantContext();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'date' => '2026-04-01',
        ]);

        Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'date' => '2026-02-10',
            'hours' => 6.00,
            'rate' => 150.00,
            'description' => 'February project work',
            'is_billable' => true,
        ]);

        Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'date' => '2026-04-12',
            'hours' => 2.00,
            'rate' => 150.00,
            'description' => 'April project work',
            'is_billable' => true,
        ]);

        $this->expectException(NonContiguousBillingPeriodException::class);

        InvoiceSyncService::sync($invoice);
    }

    protected function makeTenantContext(): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'business_name' => 'Fonts Water',
            'code' => 'FW',
        ]);

        return [$user, $tenant, $client];
    }
}
