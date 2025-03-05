<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Helpers\InvoiceNumber;
use App\Models\Client;
use App\Models\Hour;
use App\Models\Invoice;
use App\Services\InvoiceDescriptionService;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client & Invoice Details')
                    ->description('Select a client and specify the invoice details.')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'business_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if (!$get('client_id')) return;

                                $client = Client::find($get('client_id'));
                                $date = now();
                                $set('number', InvoiceNumber::generate($client, $date));

                                // Calculate totals from uninvoiced time entries for the current month
                                $hours = Hour::query()
                                    ->where('client_id', $get('client_id'))
                                    ->where('is_billable', true)
                                    ->whereNull('invoice_id')
                                    ->whereMonth('date', $date->month)
                                    ->whereYear('date', $date->year)
                                    ->get();

                                $result = InvoiceDescriptionService::generate($hours, $date);
                                $set('amount', $result['amount']);
                                $set('description', $result['description']);
                            }),

                        Forms\Components\TextInput::make('number')
                            ->label('Invoice Number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrated(),

                        Forms\Components\TextInput::make('reference')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                            ->required()
                            ->default(InvoiceStatus::Draft->value),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Dates')
                    ->description('Specify invoice and payment due dates.')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Invoice Date')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if ($get('client_id') && $get('date')) {
                                    $client = Client::find($get('client_id'));
                                    $date = \Carbon\Carbon::parse($get('date'));
                                    $set('number', InvoiceNumber::generate($client, $date));
                                }
                            }),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required()
                            ->default(now()->addDays(14)),
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

                Tables\Columns\TextColumn::make('client.business_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

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
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (Invoice $record): string => match($record->status) {
                        InvoiceStatus::Draft => 'gray',
                        InvoiceStatus::Sent => 'warning',
                        InvoiceStatus::Paid => 'success',
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Invoice $record): string => route('invoice.view', ['invoice' => $record, 'print' => true]))
                    ->openUrlInNewTab(),
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
