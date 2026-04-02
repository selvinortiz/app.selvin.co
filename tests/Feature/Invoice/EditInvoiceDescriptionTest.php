<?php

namespace Tests\Feature\Invoice;

use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MocksOpenAIResponses;
use Tests\TestCase;

class EditInvoiceDescriptionTest extends TestCase
{
    use MocksOpenAIResponses;
    use RefreshDatabase;

    public function test_generate_description_uses_the_persisted_billing_period(): void
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
            'description' => 'Old description',
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
            'hours' => 2.50,
            'rate' => 150.00,
            'description' => 'March project work',
            'is_billable' => true,
        ]);

        $this->mockOpenAiFailure();

        Livewire::test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
            ->call('generateDescription')
            ->assertFormSet([
                'description' => "Professional Services for February – March 2026\n\n- February project work (6.00 hours @ \$150.00/hr) = \$900.00\n- March project work (2.50 hours @ \$150.00/hr) = \$375.00\n\nTotal Hours: 8.50\nTotal Amount: \$1,275.00",
                'amount' => 1275.0,
            ]);
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
