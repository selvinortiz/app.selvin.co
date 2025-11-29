<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\HourResource\Pages\ListHours;
use App\Services\MonthContextService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HourStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getTablePage(): string
    {
        return ListHours::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalHours = $query->sum('hours');
        $totalAmount = $query->get()->sum(fn ($record) => $record->hours * $record->rate);
        $recordCount = $query->count();

        return [
            Stat::make('Total Hours', number_format($totalHours, 1) . ' hrs')
                ->description('Hours logged for ' . MonthContextService::getFormattedMonth())
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before),

            Stat::make('Total Amount', '$' . number_format($totalAmount, 2))
                ->description('Billing amount for ' . MonthContextService::getFormattedMonth())
                ->descriptionIcon('heroicon-m-currency-dollar', IconPosition::Before)
                ->color('success'),

            // Stat::make('Time Entries', $recordCount)
            //     ->description('Time logged in ' . MonthContextService::getFormattedMonth())
            //     ->descriptionIcon('heroicon-m-document-text', IconPosition::Before)
            //     ->color('info'),
        ];
    }
}
