<?php

namespace App\Filament\Widgets;

use App\Models\ContractorInvoice;
use App\Services\MonthContextService;
use Filament\Facades\Filament;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ContractorInvoiceSummary extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected $listeners = ['month-context-updated' => '$refresh'];

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $userId = Auth::id();
        $selectedMonth = MonthContextService::getSelectedMonth();

        // Get unpaid invoices
        $unpaidInvoices = ContractorInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->whereNull('paid_at');

        $unpaidCount = $unpaidInvoices->count();
        $unpaidAmount = $unpaidInvoices->sum('amount');

        // Get overdue invoices
        $overdueInvoices = ContractorInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->whereNull('paid_at')
            ->where('due_date', '<', now());

        $overdueCount = $overdueInvoices->count();
        $overdueAmount = $overdueInvoices->sum('amount');

        // Get selected month's paid amount
        $selectedMonthPaid = ContractorInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $selectedMonth->year)
            ->whereMonth('paid_at', $selectedMonth->month)
            ->sum('amount');

        return [
            Stat::make('Unpaid Invoices', $unpaidCount)
                ->description('$' . number_format($unpaidAmount, 2))
                ->descriptionIcon('heroicon-m-document-text', IconPosition::Before)
                ->color('warning'),

            Stat::make('Overdue Invoices', $overdueCount)
                ->description('$' . number_format($overdueAmount, 2))
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color('danger'),

            Stat::make('Paid This Month', '$' . number_format($selectedMonthPaid, 2))
                ->description(MonthContextService::getFormattedMonth())
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('success'),
        ];
    }
}
