<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\HourResource\Pages\ListHours;
use App\Models\Hour;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class HourStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

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
                ->description('Hours logged')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('Total Amount', '$' . number_format($totalAmount, 2))
                ->description('Billing amount')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Entries', $recordCount)
                ->description('Time entries')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
