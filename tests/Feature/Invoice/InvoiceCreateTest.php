<?php

namespace Tests\Feature\Invoice;

use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MonthContextService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_invoice_for_a_contiguous_multi_month_range(): void
    {
        [$user, $tenant] = $this->authenticateForPanel();
        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'business_name' => 'Fonts Water',
            'code' => 'FW',
            'payment_terms_days' => 15,
        ]);

        $februaryHour = Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => null,
            'date' => '2026-02-10',
            'hours' => 6.00,
            'rate' => 150.00,
            'description' => 'February project work',
            'is_billable' => true,
        ]);

        $marchHour = Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => null,
            'date' => '2026-03-12',
            'hours' => 2.00,
            'rate' => 150.00,
            'description' => 'March project work',
            'is_billable' => true,
        ]);

        $januaryHour = Hour::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_id' => null,
            'date' => '2026-01-22',
            'hours' => 1.00,
            'rate' => 150.00,
            'description' => 'January project work',
            'is_billable' => true,
        ]);

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'client_id' => $client->id,
                'number' => 'FW20260401',
                'date' => '2026-04-01',
                'due_date' => '2026-04-16',
                'reference' => 'TLAPR2026',
                'billing_months' => ['2026-02', '2026-03'],
                'amount' => 1,
                'description' => 'Manual catch-up invoice description',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $invoice = Invoice::query()->latest('id')->firstOrFail();

        $this->assertSame($tenant->id, $invoice->tenant_id);
        $this->assertSame('2026-02-01', $invoice->billing_period_start->toDateString());
        $this->assertSame('2026-03-01', $invoice->billing_period_end->toDateString());
        $this->assertSame('TLFEBMAR2026', $invoice->reference);
        $this->assertSame('1200.00', $invoice->amount);

        $this->assertSame($invoice->id, $februaryHour->fresh()->invoice_id);
        $this->assertSame($invoice->id, $marchHour->fresh()->invoice_id);
        $this->assertNull($januaryHour->fresh()->invoice_id);
    }

    public function test_default_billing_month_tracks_invoice_date_until_the_user_customizes_it(): void
    {
        session([MonthContextService::SESSION_KEY => '2026-04-01']);

        [$user, $tenant] = $this->authenticateForPanel();
        $client = Client::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'business_name' => 'Fonts Water',
            'code' => 'FW2',
        ]);

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'client_id' => $client->id,
            ])
            ->assertFormSet([
                'billing_months' => ['2026-04'],
            ])
            ->fillForm([
                'date' => '2026-03-28',
            ])
            ->assertFormSet([
                'billing_months' => ['2026-03'],
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
