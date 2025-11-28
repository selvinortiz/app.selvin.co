<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractorInvoiceResource\Pages;
use App\Models\ContractorInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ContractorInvoiceResource extends Resource
{
    protected static ?string $model = ContractorInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?int $navigationSort = 31;
    protected static ?string $navigationGroup = 'Contractors';
    protected static ?string $navigationLabel = 'Invoices';

    protected static function updateDueDate(Get $get, Set $set): void
    {
        if (!$get('contractor_id') || !$get('date')) {
            return;
        }

        $contractor = \App\Models\Contractor::find($get('contractor_id'));
        if ($contractor && $contractor->payment_terms_days) {
            $date = \Carbon\Carbon::parse($get('date'));
            $set('due_date', $date->copy()->addDays($contractor->payment_terms_days));
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contractor & Invoice Details')
                    ->description('Select a contractor and enter invoice information.')
                    ->schema([
                        Forms\Components\Select::make('contractor_id')
                            ->relationship('contractor', 'name', fn ($query) => $query->where('user_id', Auth::id()))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::updateDueDate($get, $set)),

                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Invoice number from contractor'),

                        Forms\Components\DatePicker::make('date')
                            ->label('Invoice Date')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::updateDueDate($get, $set)),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->maxValue(PHP_INT_MAX),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Payment Tracking')
                    ->description('Track when this invoice was paid.')
                    ->schema([
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At')
                            ->helperText('Leave empty if not yet paid')
                            ->displayFormat('M d, Y g:i A')
                            ->timezone('America/Chicago'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Document')
                    ->description('Upload the invoice PDF.')
                    ->schema([
                        Forms\Components\FileUpload::make('pdf_path')
                            ->label('Invoice PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480) // 20MB
                            ->disk('s3')
                            ->directory('contractor/invoices')
                            ->visibility('private')
                            ->helperText('Maximum file size: 20MB')
                            ->downloadable()
                            ->previewable()
                            ->openable()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contractor.name')
                    ->label('Contractor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn (ContractorInvoice $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('amount')
                    ->money()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status')
                    ->state(fn (ContractorInvoice $record): string => $record->isPaid() ? 'Paid' : ($record->isOverdue() ? 'Overdue' : 'Unpaid'))
                    ->badge()
                    ->color(fn (ContractorInvoice $record): string => match (true) {
                        $record->isPaid() => 'success',
                        $record->isOverdue() => 'danger',
                        default => 'warning',
                    })
                    ->sortable(query: function ($query, string $direction): \Illuminate\Database\Eloquent\Builder {
                        return $query->orderBy('paid_at', $direction);
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contractor_id')
                    ->label('Contractor')
                    ->relationship('contractor', 'name', fn ($query) => $query->where('user_id', Auth::id()))
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('payment_status')
                    ->label('Payment Status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'paid' => 'Paid',
                                'unpaid' => 'Unpaid',
                                'overdue' => 'Overdue',
                            ]),
                    ])
                    ->query(function ($query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return match ($data['status'] ?? null) {
                            'paid' => $query->whereNotNull('paid_at'),
                            'unpaid' => $query->whereNull('paid_at'),
                            'overdue' => $query->whereNull('paid_at')->where('due_date', '<', now()),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')
                            ->label('Due From'),
                        Forms\Components\DatePicker::make('due_until')
                            ->label('Due Until'),
                    ])
                    ->query(function ($query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn ($query, $date) => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn ($query, $date) => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ContractorInvoice $record) {
                        $record->update(['paid_at' => now()]);
                        \Filament\Notifications\Notification::make()
                            ->title('Invoice marked as paid')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ContractorInvoice $record) => !$record->isPaid()),
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (ContractorInvoice $record) {
                        if ($record->pdf_path && Storage::disk('s3')->exists($record->pdf_path)) {
                            $file = Storage::disk('s3')->get($record->pdf_path);
                            $fileName = basename($record->pdf_path);

                            return response()->streamDownload(function () use ($file) {
                                echo $file;
                            }, $fileName, [
                                'Content-Type' => 'application/pdf',
                            ]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('PDF not found')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (ContractorInvoice $record) => !empty($record->pdf_path)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContractorInvoices::route('/'),
            'create' => Pages\CreateContractorInvoice::route('/create'),
            'edit' => Pages\EditContractorInvoice::route('/{record}/edit'),
        ];
    }
}
