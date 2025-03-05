<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Hour;
use Carbon\Carbon;
use Filament\Actions;
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
}
