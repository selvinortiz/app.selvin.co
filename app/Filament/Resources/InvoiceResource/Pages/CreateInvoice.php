<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Exceptions\NonContiguousBillingPeriodException;
use App\Filament\Resources\InvoiceResource;
use App\Models\Hour;
use App\Services\InvoiceBillingPeriodService;
use App\Services\InvoiceDescriptionService;
use App\Services\InvoiceSyncService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected array $selectedBillingMonths = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $invoiceDate = Carbon::parse($data['date']);
        $billingMonths = $data['billing_months'] ?? [$invoiceDate->format('Y-m')];

        try {
            $this->selectedBillingMonths = InvoiceBillingPeriodService::normalizeMonthKeys($billingMonths);
        } catch (NonContiguousBillingPeriodException $e) {
            throw ValidationException::withMessages([
                'billing_months' => $e->getMessage(),
            ]);
        }

        [$billingPeriodStart, $billingPeriodEnd] = InvoiceBillingPeriodService::fromMonthKeys($this->selectedBillingMonths);
        $data['billing_period_start'] = $billingPeriodStart->toDateString();
        $data['billing_period_end'] = $billingPeriodEnd->toDateString();
        $data['reference'] = InvoiceBillingPeriodService::buildReference($billingPeriodStart, $billingPeriodEnd);
        unset($data['billing_months']);

        return $data;
    }

    protected function afterCreate(): void
    {
        [$billingPeriodStart, $billingPeriodEnd] = InvoiceBillingPeriodService::fromMonthKeys($this->selectedBillingMonths);

        $this->hoursQueryForRange($this->record->client_id, $billingPeriodStart, $billingPeriodEnd)
            ->update(['invoice_id' => $this->record->id]);

        $this->record = InvoiceSyncService::sync($this->record);
    }

    public function generateDescription(): void
    {
        $data = $this->data ?? [];
        $clientId = $data['client_id'] ?? null;
        $date = $data['date'] ?? null;

        if (!$clientId || !$date) {
            Notification::make()
                ->title('Missing Information')
                ->body('Please select a client and invoice date first.')
                ->warning()
                ->send();
            return;
        }

        $date = Carbon::parse($date);
        $billingMonths = $data['billing_months'] ?? [$date->format('Y-m')];

        try {
            $billingMonths = InvoiceBillingPeriodService::normalizeMonthKeys($billingMonths);
        } catch (NonContiguousBillingPeriodException $e) {
            Notification::make()
                ->title('Invalid Billing Period')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }

        [$billingPeriodStart, $billingPeriodEnd] = InvoiceBillingPeriodService::fromMonthKeys($billingMonths);

        $hours = $this->hoursQueryForRange($clientId, $billingPeriodStart, $billingPeriodEnd)->get();

        if ($hours->isEmpty()) {
            Notification::make()
                ->title('No Hours Found')
                ->body('No billable hours found for the selected client and months.')
                ->warning()
                ->send();
            return;
        }

        $details = InvoiceDescriptionService::generate(
            $hours,
            $date,
            InvoiceBillingPeriodService::buildLabel($billingPeriodStart, $billingPeriodEnd)
        );

        $this->data['description'] = $details['description'];
        $this->data['amount'] = $details['amount'];
        $this->form->fill($this->data);

        Notification::make()
            ->title('Description Generated')
            ->body('Invoice description and amount have been generated from available hours.')
            ->success()
            ->send();
    }

    protected function hoursQueryForRange(int $clientId, Carbon $billingPeriodStart, Carbon $billingPeriodEnd)
    {
        return Hour::query()
            ->where('client_id', $clientId)
            ->where('is_billable', true)
            ->whereNull('invoice_id')
            ->whereBetween('date', [
                $billingPeriodStart->copy()->startOfMonth()->toDateString(),
                $billingPeriodEnd->copy()->endOfMonth()->toDateString(),
            ]);
    }
}
