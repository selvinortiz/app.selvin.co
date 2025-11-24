<?php

namespace App\Filament\Resources\ContractorInvoiceResource\Pages;

use App\Filament\Resources\ContractorInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractorInvoice extends EditRecord
{
    protected static string $resource = ContractorInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
