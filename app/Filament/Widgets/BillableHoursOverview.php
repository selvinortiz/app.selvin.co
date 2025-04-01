<?php

namespace App\Filament\Widgets;

use App\Models\Hour;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillableHoursOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $now = now(config('app.user_timezone', 'UTC'));

        $userId = Auth::id();


        // Get current month's billable hours
        $currentMonthEntries = Hour::query()
            ->where('user_id', $userId)
            ->where('is_billable', true)
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month);

        $totalHours = $currentMonthEntries->sum('hours');
        $totalAmount = $currentMonthEntries->sum(DB::raw('hours * rate'));

        // Get unbilled entries
        $unbilledEntries = Hour::query()
            ->where('user_id', $userId)
            ->where('is_billable', true)
            ->whereNull('invoice_id')
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month);

        $unbilledHours = $unbilledEntries->sum('hours');
        $unbilledAmount = $unbilledEntries->sum(DB::raw('hours * rate'));

        // Calculate average hourly rate
        $averageRate = Hour::query()
            ->where('user_id', $userId)
            ->where('is_billable', true)
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->avg('rate') ?? 0;

        return [
            Stat::make('Current Month Hours', number_format($totalHours, 2))
                ->description($now->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->chart([7, 4, 6, 8, 5, 2, 3])
                ->color('success'),

            Stat::make('Unbilled Time', number_format($unbilledHours, 2) . ' hours')
                ->description('$' . number_format($unbilledAmount, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Average Rate', '$' . number_format($averageRate, 2))
                ->description('Per hour')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }
}
