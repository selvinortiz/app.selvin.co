<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationGroup = 'Clients';

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

                        Forms\Components\TextInput::make('short_name')
                            ->label('Short Name')
                            ->maxLength(255)
                            ->helperText('Optional alias for use in tables (e.g., "Resi" for "MyCore Industries, LLC")')
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

                Forms\Components\Section::make('Client Portal')
                    ->schema([
                        Forms\Components\Placeholder::make('portal_url_display')
                            ->label('Portal URL')
                            ->content(fn (?Client $record): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                                $record?->portal_token
                                    ? '<div class="flex items-center gap-2">'
                                        . '<a href="' . e($record->portal_url) . '" target="_blank" class="text-primary-600 hover:underline break-all">' . e($record->portal_url) . '</a>'
                                        . '</div>'
                                    : '<span class="text-gray-500">No portal link generated yet. Use the Generate action below.</span>'
                            ))
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('generate_portal_token')
                                ->label('Generate Portal Link')
                                ->icon('heroicon-o-link')
                                ->color('primary')
                                ->visible(fn (?Client $record): bool => $record && $record->portal_token === null)
                                ->action(function (Client $record): void {
                                    $record->generatePortalToken();
                                    Notification::make()
                                        ->title('Portal link generated')
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('copy_portal_url')
                                ->label('Copy Link')
                                ->icon('heroicon-o-clipboard-document')
                                ->color('gray')
                                ->visible(fn (?Client $record): bool => $record && $record->portal_token !== null)
                                ->extraAttributes(fn (?Client $record): array => [
                                    'x-on:click' => "navigator.clipboard.writeText('" . ($record?->portal_url ?? '') . "')",
                                ])
                                ->action(function (Client $record): void {
                                    Notification::make()
                                        ->title('Portal link copied to clipboard')
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('revoke_portal_token')
                                ->label('Revoke Link')
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->visible(fn (?Client $record): bool => $record && $record->portal_token !== null)
                                ->requiresConfirmation()
                                ->action(function (Client $record): void {
                                    $record->revokePortalToken();
                                    Notification::make()
                                        ->title('Portal link revoked')
                                        ->success()
                                        ->send();
                                }),
                        ]),
                    ])
                    ->collapsible()
                    ->hiddenOn('create'),
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

                Tables\Columns\TextColumn::make('short_name')
                    ->label('AKA')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('default_rate')
                    ->label('Rate')
                    ->money()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payment_terms_days')
                    ->label('Terms')
                    ->formatStateUsing(fn (int $state): string => "{$state} days")
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_portal')
                    ->label('')
                    ->tooltip('View Portal')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->url(fn (Client $record): ?string => $record->portal_url)
                    ->openUrlInNewTab()
                    ->visible(fn (Client $record): bool => $record->portal_token !== null),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('generate_portal_link')
                        ->label('Generate Portal Link')
                        ->icon('heroicon-o-link')
                        ->visible(fn (Client $record): bool => $record->portal_token === null)
                        ->action(function (Client $record): void {
                            $record->generatePortalToken();
                            Notification::make()
                                ->title('Portal link generated')
                                ->body($record->portal_url)
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('copy_portal_link')
                        ->label('Copy Portal Link')
                        ->icon('heroicon-o-clipboard-document')
                        ->visible(fn (Client $record): bool => $record->portal_token !== null)
                        ->extraAttributes(fn (Client $record): array => [
                            'x-on:click' => "navigator.clipboard.writeText('{$record->portal_url}')",
                        ])
                        ->action(function (Client $record): void {
                            Notification::make()
                                ->title('Portal link copied')
                                ->body($record->portal_url)
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('revoke_portal_link')
                        ->label('Revoke Portal Link')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Client $record): bool => $record->portal_token !== null)
                        ->requiresConfirmation()
                        ->action(function (Client $record): void {
                            $record->revokePortalToken();
                            Notification::make()
                                ->title('Portal link revoked')
                                ->success()
                                ->send();
                        }),
                ]),
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
