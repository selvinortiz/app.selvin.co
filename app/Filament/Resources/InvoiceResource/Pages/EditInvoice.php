<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceDescriptionService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    #[On('refreshInvoice')]
    public function refresh(): void
    {
        $this->getRecord()->refresh();
        $this->fillForm();
    }

    public function generateDescription(): void
    {
        Log::debug('EditInvoice.generateDescription invoked', [
            'invoice_id' => $this->record?->id,
        ]);

        $invoice = $this->getRecord();
        $hours = $invoice->hours()->get();

        Log::debug('EditInvoice.generateDescription hours loaded', [
            'count' => $hours->count(),
        ]);

        if ($hours->isEmpty()) {
            Notification::make()
                ->title('No Hours Found')
                ->body('No hours are currently linked to this invoice.')
                ->warning()
                ->send();
            return;
        }

        $details = InvoiceDescriptionService::generate($hours, $invoice->date);

        // Update form data directly without triggering validation
        $this->form->fill([
            ...$this->form->getState(),
            'description' => $details['description'],
            'amount' => $details['amount'],
        ]);

        Notification::make()
            ->title('Description Generated')
            ->body('Invoice description and amount have been generated from linked hours.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
