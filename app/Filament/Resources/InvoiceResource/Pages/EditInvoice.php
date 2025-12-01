<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceDescriptionService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
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
        $invoice = $this->getRecord();
        $hours = $invoice->hours()->get();

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
        $this->data['description'] = $details['description'];
        $this->data['amount'] = $details['amount'];

        // Update the form state
        $this->form->fill($this->data);

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
