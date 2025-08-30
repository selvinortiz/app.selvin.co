<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HourResource\Pages;
use App\Models\Hour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HourResource extends Resource
{
    protected static ?string $model = Hour::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client & Date')
                    ->description('Select the client and date for this time entry.')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'business_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if (!$get('client_id')) return;

                                $client = \App\Models\Client::find($get('client_id'));
                                if ($client) {
                                    $set('rate', $client->default_rate);
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Entry Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->helperText('When was this work performed?'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Time & Billing')
                    ->description('Enter the hours worked and billing details.')
                    ->schema([
                        Forms\Components\TextInput::make('hours')
                            ->required()
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0.5)
                            ->maxValue(24)
                            ->suffix('hours')
                            ->helperText('Enter time in decimal hours (e.g., 1.5 for 1 hour 30 minutes)'),

                        Forms\Components\TextInput::make('rate')
                            ->label('Hourly Rate')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(1)
                            ->default(150)
                            ->helperText('Default rate is set based on client preferences'),

                        Forms\Components\Toggle::make('is_billable')
                            ->label('Billable Time')
                            ->required()
                            ->default(true)
                            ->inline(false)
                            ->helperText('Should this time be billed to the client?')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Work Description')
                    ->description('Provide a detailed description of the work performed.')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(3)
                            ->placeholder('Describe the work performed in detail...')
                            ->helperText('Be specific about tasks completed and deliverables')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $now = now(config('app.user_timezone', 'UTC'));

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.business_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Entry Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Time')
                    ->numeric(1)
                    ->sortable()
                    ->suffix(' hrs')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->money('USD')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),


                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_billable')
                    ->label('Billable')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('invoice.number')
                    ->label('Invoice')
                    ->sortable()
                    ->placeholder('Not Invoiced')
                    ->url(fn ($record) => $record->invoice_id ? route('invoice.view', $record->invoice) : null)
                    ->toggleable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('client')
                    ->relationship('client', 'business_name')
                    ->label('Filter by Client')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('uninvoiced')
                    ->label('Show Uninvoiced')
                    ->query(fn (Builder $query): Builder => $query->whereNull('invoice_id')),

                Tables\Filters\Filter::make('billable')
                    ->label('Show Billable')
                    ->query(fn (Builder $query): Builder => $query->where('is_billable', true)),

                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('date', $now->month)
                        ->whereYear('date', $now->year))
                    ->default(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHours::route('/'),
            'create' => Pages\CreateHour::route('/create'),
            'edit' => Pages\EditHour::route('/{record}/edit'),
        ];
    }
}
