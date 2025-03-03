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
                // Forms\Components\Select::make('tenant_id')
                //     ->relationship('tenant', 'name')
                //     ->required(),
                // Forms\Components\Select::make('user_id')
                //     ->relationship('user', 'name')
                //     ->required(),
                Forms\Components\TextInput::make('business_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('business_phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('business_email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tax_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('website')
                    ->maxLength(255),
                Forms\Components\TextInput::make('default_rate')
                    ->required()
                    ->numeric()
                    ->default(150),
                Forms\Components\TextInput::make('contact_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_title')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Toggle::make('send_invoices_to_contact')
                    ->required(),
                Forms\Components\TextInput::make('payment_terms_days')
                    ->required()
                    ->numeric()
                    ->default(14),
                Forms\Components\Textarea::make('invoice_notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('internal_notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('tenant.name')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('user.name')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('business_phone')
                //     ->label('Phone')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('business_email')
                //     ->label('Email')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('tax_id')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('website')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('default_rate')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('contact_title')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('contact_email')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('contact_phone')
                //     ->searchable(),
                // Tables\Columns\IconColumn::make('send_invoices_to_contact')
                //     ->boolean(),
                Tables\Columns\TextColumn::make('code')
                ->searchable(),
                Tables\Columns\TextColumn::make('default_rate')
                    ->label('Rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_terms_days')
                    ->label('Terms')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
