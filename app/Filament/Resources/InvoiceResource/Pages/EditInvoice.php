<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceDescriptionService;
use App\Services\InvoiceSyncService;
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
        $invoice = InvoiceSyncService::sync($this->getRecord());
        $hours = $invoice->hours;

        if ($hours->isEmpty()) {
            Notification::make()
                ->title('No Hours Found')
                ->body('No hours are currently linked to this invoice.')
                ->warning()
                ->send();

            return;
        }

        $details = InvoiceDescriptionService::generate(
            $hours,
            $invoice->date,
            $invoice->billing_period_label
        );

        $this->form->fill([
            ...$this->form->getState(),
            'description' => $details['description'],
            'amount' => $details['amount'],
        ]);

        $notification = Notification::make();

        if ($details['used_ai']) {
            $notification
                ->title('Description Generated')
                ->body('Invoice description and amount have been generated from linked hours.')
                ->success();
        } else {
            $notification
                ->title('Fallback Description Generated')
                ->body(static::fallbackNotificationBody($details['fallback_reason']))
                ->warning();
        }

        $notification->send();
    }

    protected static function fallbackNotificationBody(?string $reason): string
    {
        $body = 'OpenAI could not generate the summary, so the line-item fallback was used.';
        $displayReason = InvoiceDescriptionService::fallbackReasonForDisplay($reason);

        if ($displayReason) {
            $body .= " Reason: {$displayReason}";
        }

        return $body;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
