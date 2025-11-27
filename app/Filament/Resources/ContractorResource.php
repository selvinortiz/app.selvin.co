<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractorResource\Pages;
use App\Filament\Resources\ContractorResource\RelationManagers;
use App\Models\Contractor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractorResource extends Resource
{
    protected static ?string $model = Contractor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationGroup = 'Contractors';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('country')
                            ->maxLength(255)
                            ->placeholder('e.g., Guatemala')
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'ACH' => 'ACH',
                                'Wire' => 'Wire',
                            ])
                            ->placeholder('Select payment method'),

                        Forms\Components\TextInput::make('payment_terms_days')
                            ->label('Payment Terms (Days)')
                            ->helperText('Default: Net 15 or Net 30')
                            ->numeric()
                            ->default(15)
                            ->required(),

                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->maxLength(255),

                        Forms\Components\Select::make('account_type')
                            ->label('Account Type')
                            ->options([
                                'checking' => 'Checking',
                                'savings' => 'Savings',
                                'other' => 'Other',
                            ])
                            ->placeholder('Select account type')
                            ->native(false),

                        Forms\Components\TextInput::make('bank_routing')
                            ->label('Bank Routing Number')
                            ->helperText('Encrypted - leave blank to keep existing value')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => !empty($state)),

                        Forms\Components\TextInput::make('bank_account')
                            ->label('Bank Account Number')
                            ->helperText('Encrypted - leave blank to keep existing value')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => !empty($state)),
                    ])
                    ->columns(2)
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_terms_days')
                    ->label('Terms')
                    ->formatStateUsing(fn (int $state): string => "Net {$state}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'ACH' => 'ACH',
                        'Wire' => 'Wire',
                    ]),
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
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContractors::route('/'),
            'create' => Pages\CreateContractor::route('/create'),
            'edit' => Pages\EditContractor::route('/{record}/edit'),
        ];
    }
}
