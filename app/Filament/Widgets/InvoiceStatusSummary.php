<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceStatusSummary extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $userId = Auth::id();
        $currentMonth = now()->startOfMonth();

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

        // Get current month's invoiced amount
        $currentMonthAmount = Invoice::query()
            ->where('user_id', $userId)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->sum('amount');

        return [
            Stat::make('Draft Invoices', $draftCount)
                ->description('$' . number_format($draftAmount, 2))
                ->descriptionIcon('heroicon-m-document')
                ->color('info'),

            Stat::make('Overdue Invoices', $overdueCount)
                ->description('$' . number_format($overdueAmount, 2))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Current Month', '$' . number_format($currentMonthAmount, 2))
                ->description($currentMonth->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([7, 4, 6, 8, 5, 2, 3])
                ->color('success'),
        ];
    }
}
