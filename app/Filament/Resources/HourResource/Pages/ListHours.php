<?php

namespace App\Filament\Resources\HourResource\Pages;

use App\Filament\Resources\HourResource;
use App\Filament\Widgets\HourStatsWidget;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListHours extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = HourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HourStatsWidget::class,
        ];
    }
}
