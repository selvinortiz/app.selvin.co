<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Helpers\InvoiceNumber;
use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Services\InvoiceDescriptionService;
use App\Services\MonthContextService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 20;
    protected static ?string $navigationGroup = 'Clients';

    protected static function updateInvoiceForMonth(Get $get, Set $set) {
        if (!$get('client_id')) return;

        $date = MonthContextService::getSelectedMonth();

        if ($get('date'))
        {
            $date = Carbon::parse($get('date'));
        }

        $client = Client::find($get('client_id'));

        $set('number', InvoiceNumber::generate($client, $date));

        $hours = Hour::query()
            ->where('client_id', $get('client_id'))
            ->where('is_billable', true)
            ->whereNull('invoice_id')
            ->whereMonth('date', $date->month)
            ->whereYear('date', $date->year)
            ->get();

        $details = InvoiceDescriptionService::generate($hours, $date);

        // Remove lines that match the summary pattern (e.g., "Total Hours (X.X)")
        $lines = array_filter(explode(PHP_EOL, $get('description') ?? ''), function($line) {
            return !preg_match('/^Total Hours \(\d+\.?\d*\)$/', trim($line));
        });

        $set('amount', $details['amount']);
        $set('description', trim(implode(PHP_EOL, array_filter([
            implode(PHP_EOL, $lines),
            $details['description'] ?? '',
        ]))));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client & Invoice Details')
                    ->description('Select a client and specify the invoice details.')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'business_name')
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->display_name)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::updateInvoiceForMonth($get, $set)),
                        Forms\Components\TextInput::make('number')
                            ->label('Invoice Number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrated(),

                        Forms\Components\TextInput::make('reference')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Dates')
                    ->description('Specify invoice and payment due dates.')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Invoice Date')
                            ->required()
                            ->default(MonthContextService::getSelectedMonth()->toDateString())
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::updateInvoiceForMonth($get, $set)),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required()
                            ->default(MonthContextService::getSelectedMonth()->addDays(15)),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Payment Tracking')
                    ->description('Track when this invoice was sent and paid.')
                    ->schema([
                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Sent At')
                            ->helperText('Leave empty if not yet sent')
                            ->displayFormat('M d, Y g:i A')
                            ->timezone('America/Chicago'),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At')
                            ->helperText('Leave empty if not yet paid')
                            ->displayFormat('M d, Y g:i A')
                            ->timezone('America/Chicago'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Amount & Description')
                    ->description('Enter the invoice amount and detailed description.')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Invoice Amount')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->maxValue(PHP_INT_MAX),

                        Forms\Components\MarkdownEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Invoice $record): string => route('invoice.view', $record)),

                Tables\Columns\TextColumn::make('client.display_name')
                    ->label('Client')
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('client', function ($q) use ($search) {
                            $q->where('business_name', 'like', "%{$search}%")
                              ->orWhere('short_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->join('clients', 'invoices.client_id', '=', 'clients.id')
                            ->orderByRaw("COALESCE(clients.short_name, clients.business_name) {$direction}")
                            ->select('invoices.*');
                    }),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (Invoice $record): string => $record->getStatusLabel())
                    ->badge()
                    ->color(fn (Invoice $record): string => $record->getStatusColor()),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                            ])
                            ->placeholder('All statuses'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['status'] === 'draft',
                            fn ($query) => $query->whereNull('sent_at'),
                        )->when(
                            $data['status'] === 'sent',
                            fn ($query) => $query->whereNotNull('sent_at')->whereNull('paid_at'),
                        )->when(
                            $data['status'] === 'paid',
                            fn ($query) => $query->whereNotNull('paid_at'),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('mark_sent')
                        ->label('Mark as Sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->update(['sent_at' => now()]);
                            \Filament\Notifications\Notification::make()
                                ->title('Invoice marked as sent')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Invoice $record) => $record->isDraft()),
                    Tables\Actions\Action::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->update(['paid_at' => now()]);
                            \Filament\Notifications\Notification::make()
                                ->title('Invoice marked as paid')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Invoice $record) => !$record->isPaid()),
                    Tables\Actions\Action::make('print')
                        ->label('Print')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Invoice $record): string => route('invoice.view', ['invoice' => $record, 'print' => true]))
                        ->openUrlInNewTab(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->label('actions')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\InvoiceResource\RelationManagers\HoursRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
