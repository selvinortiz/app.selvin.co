<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Hour;
use App\Services\InvoiceDescriptionService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function afterCreate(): void
    {
        // Get all unbilled hours for this client in the invoice month
        $date = Carbon::parse($this->record->date);

        Hour::query()
            ->where('client_id', $this->record->client_id)
            ->where('is_billable', true)
            ->whereNull('invoice_id')
            ->whereMonth('date', $date->month)
            ->whereYear('date', $date->year)
            ->update(['invoice_id' => $this->record->id]);
    }

    public function generateDescription(): void
    {
        // Get current form data without validation
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

        // For new invoices, use unbilled hours for the client/date
        $hours = Hour::query()
            ->where('client_id', $clientId)
            ->where('is_billable', true)
            ->whereNull('invoice_id')
            ->whereMonth('date', $date->month)
            ->whereYear('date', $date->year)
            ->get();

        if ($hours->isEmpty()) {
            Notification::make()
                ->title('No Hours Found')
                ->body('No billable hours found for the selected client and date.')
                ->warning()
                ->send();
            return;
        }

        $details = InvoiceDescriptionService::generate($hours, $date);

        // Update form data directly without triggering validation
        $this->data['description'] = $details['description'];
        $this->data['amount'] = $details['amount'];

        // Update the form state
        $this->form->fill($this->data);

        Notification::make()
            ->title('Description Generated')
            ->body('Invoice description and amount have been generated from available hours.')
            ->success()
            ->send();
    }
}
