<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Exceptions\NonContiguousBillingPeriodException;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Hour;
use App\Services\InvoiceSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class HoursRelationManager extends RelationManager
{
    protected static string $relationship = 'hours';
    protected static ?string $title = 'Billable Hours';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hours')
                    ->numeric(1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money()
                    ->state(fn ($record) => $record->hours * $record->rate)
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([
                Tables\Actions\AssociateAction::make()
                    ->label('Add Hours')
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['date', 'description'])
                    ->recordTitleAttribute('description')
                    ->recordSelectOptionsQuery(fn (Builder $query, $livewire) =>
                        $query->where('client_id', $livewire->getOwnerRecord()->client_id)
                            ->where('is_billable', true)
                            ->whereNull('invoice_id')
                            ->orderBy('date', 'desc')
                    )
                    ->after(function ($record, $livewire) {
                        $invoice = $livewire->getOwnerRecord();

                        try {
                            DB::transaction(function () use ($record, $invoice): void {
                                $record->update(['invoice_id' => $invoice->id]);
                                InvoiceSyncService::sync($invoice);
                            });
                        } catch (NonContiguousBillingPeriodException $e) {
                            Notification::make()
                                ->title('Cannot Link Hours')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }

                        $livewire->dispatch('refreshInvoice');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Hour $record, $livewire) {
                        $invoice = $livewire->getOwnerRecord();

                        try {
                            DB::transaction(function () use ($record, $invoice): void {
                                $record->update(['invoice_id' => null]);
                                InvoiceSyncService::sync($invoice);
                            });
                        } catch (NonContiguousBillingPeriodException $e) {
                            Notification::make()
                                ->title('Cannot Remove Hours')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }

                        $livewire->dispatch('refreshInvoice');
                    }),
            ]);
    }
}
