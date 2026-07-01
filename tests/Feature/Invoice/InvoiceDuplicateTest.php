<?php

namespace Tests\Feature\Invoice;

use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_duplicates_an_invoice_as_a_draft_without_relinking_hours(): void
    {
        [$user, $tenant] = $this->authenticateForPanel();
        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'business_name' => 'Fonts Water',
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
            'description' => 'Manual catch-up invoice description',
            'amount' => 1200,
            'sent_at' => '2026-04-01 13:00:00',
            'paid_at' => '2026-04-16 13:00:00',
        ]);

        Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'number' => 'FW20260401-2',
            'date' => '2026-04-01',
        ]);

        $firstHour = Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'date' => '2026-02-10',
        ]);

        $secondHour = Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'date' => '2026-03-12',
        ]);

        Livewire::test(ListInvoices::class)
            ->callTableAction('duplicate', $invoice)
            ->assertHasNoTableActionErrors()
            ->assertRedirect();

        $duplicate = Invoice::query()
            ->where('number', 'FW20260401-3')
            ->firstOrFail();

        $this->assertNotSame($invoice->id, $duplicate->id);
        $this->assertSame($tenant->id, $duplicate->tenant_id);
        $this->assertSame($user->id, $duplicate->user_id);
        $this->assertSame($client->id, $duplicate->client_id);
        $this->assertSame('2026-04-01', $duplicate->date->toDateString());
        $this->assertNull($duplicate->billing_period_start);
        $this->assertNull($duplicate->billing_period_end);
        $this->assertSame('April 2026', $duplicate->billing_period_label);
        $this->assertSame('2026-04-16', $duplicate->due_date->toDateString());
        $this->assertSame('TLAPR2026', $duplicate->reference);
        $this->assertSame('Manual catch-up invoice description', $duplicate->description);
        $this->assertSame('1200.00', $duplicate->amount);
        $this->assertNull($duplicate->sent_at);
        $this->assertNull($duplicate->paid_at);
        $this->assertTrue($duplicate->isDraft());
        $this->assertSame(0, $duplicate->hours()->count());

        $this->assertSame($invoice->id, $firstHour->fresh()->invoice_id);
        $this->assertSame($invoice->id, $secondHour->fresh()->invoice_id);
    }

    protected function authenticateForPanel(): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $user->tenants()->attach($tenant);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('manage'));
        Filament::setTenant($tenant, isQuiet: true);

        return [$user, $tenant];
    }
}
