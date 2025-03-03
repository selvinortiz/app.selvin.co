<?php

namespace App\Filament\Resources\HourResource\Pages;

use App\Filament\Resources\HourResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHours extends ListRecords
{
    protected static string $resource = HourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
