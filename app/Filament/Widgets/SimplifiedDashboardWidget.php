<?php

namespace App\Filament\Widgets;

use App\Models\ContractorInvoice;
use App\Models\Hour;
use App\Services\MonthContextService;
use Filament\Facades\Filament;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SimplifiedDashboardWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected $listeners = ['month-context-updated' => '$refresh'];

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $selectedMonth = MonthContextService::getSelectedMonth();
        $userId = Auth::id();

        // Calculate Hours (all hours - billable + non-billable)
        $hoursQuery = Hour::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->whereYear('date', $selectedMonth->year)
            ->whereMonth('date', $selectedMonth->month);

        $totalHours = (clone $hoursQuery)->sum('hours');

        // Calculate Receivable (billable amount)
        $billableAmount = (clone $hoursQuery)
            ->where('is_billable', true)
            ->get()
            ->sum(fn ($record) => $record->hours * $record->rate);

        // Calculate Payable (contractor invoices paid in selected month)
        $selectedMonthPaid = ContractorInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $selectedMonth->year)
            ->whereMonth('paid_at', $selectedMonth->month)
            ->sum('amount');

        $formattedMonth = MonthContextService::getFormattedMonth();

        return [
            Stat::make('Hours', number_format($totalHours, 2))
                ->description('Hours logged for ' . $formattedMonth)
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before),

            Stat::make('Receivable', '$' . number_format($billableAmount, 2))
                ->description('Billing amount to clients for ' . $formattedMonth)
                ->descriptionIcon('heroicon-m-arrow-down-right', IconPosition::Before)
                ->color('success'),

            Stat::make('Payable', '$' . number_format($selectedMonthPaid, 2))
                ->description('Invoiced by contractors for ' . $formattedMonth)
                ->descriptionIcon('heroicon-m-arrow-up-right', IconPosition::Before)
                ->color('danger'),
        ];
    }
}
