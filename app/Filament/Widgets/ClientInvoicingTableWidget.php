<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\YearContextService;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientInvoicingTableWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected $listeners = ['year-context-updated' => '$refresh'];

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    public function getTableRecordKey(Model $record): string
    {
        // For aggregated queries with groupBy, records are stdClass objects, not Models
        // Access client_id directly since we're grouping by it
        // Use property_exists to check safely
        if (property_exists($record, 'client_id') && $record->client_id !== null) {
            return (string) $record->client_id;
        }

        // Fallback: try to get key if it's actually a Model
        try {
            return (string) $record->getKey();
        } catch (\Exception $e) {
            // If getKey() fails, generate a unique key
            return uniqid('client_', true);
        }
    }

    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        $selectedYear = YearContextService::getSelectedYear();
        $userId = Auth::id();

        $paidStatus = InvoiceStatus::Paid->value;

        return $table
            ->query(
                Invoice::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('user_id', $userId)
                    ->whereYear('date', $selectedYear)
                    ->select([
                        'client_id',
                        DB::raw('COUNT(*) as invoice_count'),
                        DB::raw('SUM(amount) as total_invoiced'),
                        DB::raw("SUM(CASE WHEN status = '{$paidStatus}' THEN amount ELSE 0 END) as total_paid"),
                    ])
                    ->with('client')
                    ->groupBy('client_id')
            )
            ->columns([
                Tables\Columns\TextColumn::make('client.display_name')
                    ->label('Client')
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('client', function ($q) use ($search) {
                            $q->where('business_name', 'like', "%{$search}%")
                              ->orWhere('short_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $paidStatus = InvoiceStatus::Paid->value;
                        return $query->join('clients', 'invoices.client_id', '=', 'clients.id')
                            ->select([
                                'invoices.client_id',
                                DB::raw('COUNT(*) as invoice_count'),
                                DB::raw('SUM(invoices.amount) as total_invoiced'),
                                DB::raw("SUM(CASE WHEN invoices.status = '{$paidStatus}' THEN invoices.amount ELSE 0 END) as total_paid"),
                            ])
                            ->groupBy('invoices.client_id')
                            ->orderByRaw("COALESCE(clients.short_name, clients.business_name) {$direction}");
                    })
                    ->default('Unknown Client'),

                Tables\Columns\TextColumn::make('invoice_count')
                    ->label('Invoice Count')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_invoiced')
                    ->label('Total Invoiced')
                    ->money('USD')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('USD')),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->money('USD')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('USD')),

                Tables\Columns\TextColumn::make('outstanding')
                    ->label('Outstanding')
                    ->money('USD')
                    ->state(fn ($record) => ($record->total_invoiced ?? 0) - ($record->total_paid ?? 0))
                    ->sortable(query: function (Builder $query, string $direction) use ($paidStatus): Builder {
                        return $query->orderByRaw("(SUM(amount) - SUM(CASE WHEN status = '{$paidStatus}' THEN amount ELSE 0 END)) {$direction}");
                    }),
            ])
            ->defaultSort('total_invoiced', 'desc')
            ->heading('Invoiced by Client in ' . YearContextService::getFormattedYear());
    }
}
