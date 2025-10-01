<?php

namespace App\Filament\Widgets;

use App\Models\Hour;
use App\Services\MonthContextService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DashboardHourStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected $listeners = ['month-context-updated' => '$refresh'];

    protected function getStats(): array
    {
        $selectedMonth = MonthContextService::getSelectedMonth();
        $userId = Auth::id();

        $query = Hour::query()
            ->where('user_id', $userId)
            ->whereYear('date', $selectedMonth->year)
            ->whereMonth('date', $selectedMonth->month);

        $totalHours = $query->sum('hours');
        $totalAmount = $query->get()->sum(fn ($record) => $record->hours * $record->rate);
        $recordCount = $query->count();

        return [
            Stat::make('Total Hours', number_format($totalHours, 1) . ' hrs')
                ->description('Hours logged for ' . MonthContextService::getFormattedMonth())
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('Total Amount', '$' . number_format($totalAmount, 2))
                ->description('Billing amount for ' . MonthContextService::getFormattedMonth())
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Entries', $recordCount)
                ->description('Time entries for ' . MonthContextService::getFormattedMonth())
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }
}
