<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\YearContextService;
use Filament\Facades\Filament;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class YTDRevenueStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected $listeners = ['year-context-updated' => '$refresh'];

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();
        $selectedYear = YearContextService::getSelectedYear();
        $userId = Auth::id();

        // Total Invoiced YTD (all invoices for the year)
        $totalInvoiced = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->whereYear('date', $selectedYear)
            ->sum('amount');

        // Total Paid YTD (only paid invoices)
        $totalPaid = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->whereYear('date', $selectedYear)
            ->where('status', InvoiceStatus::Paid)
            ->sum('amount');

        // Outstanding Receivables (Total Invoiced - Total Paid)
        $outstanding = $totalInvoiced - $totalPaid;

        // $formattedYear = YearContextService::getFormattedYear();

        return [
            Stat::make('Total Invoiced', '$' . number_format($totalInvoiced, 2))
                ->description('Total invoiced to clients')
                ->descriptionIcon('heroicon-m-document-text', IconPosition::Before),

            Stat::make('Total Paid', '$' . number_format($totalPaid, 2))
                ->description('Total paid by clients')
                ->descriptionIcon('heroicon-m-check-circle', IconPosition::Before)
                ->color('success'),

            Stat::make('Outstanding', '$' . number_format($outstanding, 2))
                ->description('Total outstanding from clients')
                ->descriptionIcon('heroicon-m-clock', IconPosition::Before)
                ->color($outstanding > 0 ? 'warning' : 'success'),
        ];
    }
}
