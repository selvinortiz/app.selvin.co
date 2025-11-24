<?php

namespace App\Filament\Resources\ContractorInvoiceResource\Pages;

use App\Filament\Resources\ContractorInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractorInvoices extends ListRecords
{
    protected static string $resource = ContractorInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
