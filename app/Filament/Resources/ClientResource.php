<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Format: ABC123'),

                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('business_phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('business_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Primary Contact')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_title')
                            ->label('Title')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('send_invoices_to_contact')
                            ->label('Send Invoices to Contact')
                            ->default(true)
                            ->helperText('Send invoices to contact email instead of business email')
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Billing Preferences')
                    ->schema([
                        Forms\Components\TextInput::make('default_rate')
                            ->label('Hourly Rate')
                            ->numeric()
                            ->prefix('$')
                            ->default(150.00)
                            ->required()
                            ->helperText('Default rate for new time entries'),

                        Forms\Components\TextInput::make('payment_terms_days')
                            ->label('Payment Terms')
                            ->helperText('Days until payment is due')
                            ->numeric()
                            ->default(15)
                            ->required(),

                        Forms\Components\TextInput::make('tax_id')
                            ->label('Tax ID')
                            ->helperText('EIN or SSN')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('invoice_notes')
                            ->label('Invoice Notes')
                            ->helperText('Default notes to appear on all invoices')
                            ->rows(3)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Internal Notes')
                            ->helperText('Notes for internal reference only')
                            ->rows(3)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('default_rate')
                    ->label('Rate')
                    ->money()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_terms_days')
                    ->label('Terms')
                    ->formatStateUsing(fn (int $state): string => "{$state} days")
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
