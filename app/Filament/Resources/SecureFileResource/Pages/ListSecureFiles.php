<?php

namespace App\Filament\Resources\SecureFileResource\Pages;

use App\Filament\Resources\SecureFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSecureFiles extends ListRecords
{
    protected static string $resource = SecureFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

