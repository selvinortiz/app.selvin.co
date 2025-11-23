<?php

namespace App\Filament\Resources\HourResource\Pages;

use App\Filament\Resources\HourResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHour extends EditRecord
{
    protected static string $resource = HourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
