<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\MonthContextService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceStatusSummary extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected $listeners = ['month-context-updated' => '$refresh'];

    protected function getStats(): array
    {
        $userId = Auth::id();
        $selectedMonth = MonthContextService::getSelectedMonth();

        // Get draft invoices
        $draftInvoices = Invoice::query()
            ->where('user_id', $userId)
            ->where('status', InvoiceStatus::Draft);

        $draftCount = $draftInvoices->count();
        $draftAmount = $draftInvoices->sum('amount');

        // Get overdue invoices
        $overdueInvoices = Invoice::query()
            ->where('user_id', $userId)
            ->where('status', InvoiceStatus::Sent)
            ->where('due_date', '<', now());

        $overdueCount = $overdueInvoices->count();
        $overdueAmount = $overdueInvoices->sum('amount');

        // Get selected month's invoiced amount
        $selectedMonthAmount = Invoice::query()
            ->where('user_id', $userId)
            ->whereYear('date', $selectedMonth->year)
            ->whereMonth('date', $selectedMonth->month)
            ->sum('amount');

        return [
            Stat::make('Draft Invoices', $draftCount)
                ->description('$' . number_format($draftAmount, 2))
                ->descriptionIcon('heroicon-m-document', IconPosition::Before),

            Stat::make('Overdue Invoices', $overdueCount)
                ->description('$' . number_format($overdueAmount, 2))
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color('danger'),

            Stat::make('Total Invoiced', '$' . number_format($selectedMonthAmount, 2))
                ->description(MonthContextService::getFormattedMonth())
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('success'),
        ];
    }
}
