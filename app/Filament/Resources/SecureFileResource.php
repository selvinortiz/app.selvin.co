<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecureFileResource\Pages;
use App\Models\Client;
use App\Models\SecureFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SecureFileResource extends Resource
{
    protected static ?string $model = SecureFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('File Information')
                    ->description('Upload the file you want to share securely.')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('File')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'application/zip', 'image/*', 'text/*'])
                            ->maxSize(50 * 1024) // 50MB
                            ->directory('secure-files')
                            ->preserveFilenames()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('filename', $state->getClientOriginalName());
                                    $set('mime_type', $state->getMimeType());
                                    $set('file_size', $state->getSize());
                                    $set('name', pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME));
                                }
                            }),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('File Details')
                    ->description('Basic information about the file.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('client_id')
                            ->label('Associated Client')
                            ->relationship('client', 'business_name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Optional - Leave empty for general files'),

                        Forms\Components\TextInput::make('filename')
                            ->label('Original Filename')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Security Settings')
                    ->description('Configure access controls for the file.')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Password Protection')
                            ->placeholder('Leave empty for no password')
                            ->helperText('Optional password that clients must enter to download the file'),

                        Forms\Components\TextInput::make('download_limit')
                            ->label('Download Limit')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Number of times this file can be downloaded (1 = one-time use)'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiration Date')
                            ->placeholder('Leave empty for no expiration')
                            ->helperText('Optional expiration date after which the file cannot be downloaded'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.business_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('General'),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (SecureFile $record): string => $record->getFormattedFileSize())
                    ->sortable(),

                Tables\Columns\TextColumn::make('download_count')
                    ->label('Downloads')
                    ->formatStateUsing(fn (SecureFile $record): string => "{$record->download_count}/{$record->download_limit}")
                    ->sortable(),

                Tables\Columns\IconColumn::make('password')
                    ->label('Password Protected')
                    ->boolean()
                    ->getStateUsing(fn (SecureFile $record): bool => !empty($record->password)),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'business_name'),
                Tables\Filters\TernaryFilter::make('has_password')
                    ->label('Password Protected')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('password'),
                        false: fn ($query) => $query->whereNull('password'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('copy_link')
                    ->icon('heroicon-o-link')
                    ->label('Copy Link')
                    ->action(function (SecureFile $record) {
                        return $record->getDownloadUrl();
                    })
                    ->extraAttributes(['x-data' => '{}'])
                    ->extraAttributes(['x-on:click' => 'navigator.clipboard.writeText($event.target.getAttribute("data-url")); $dispatch("notify", {message: "Link copied to clipboard!"})'])
                    ->extraAttributes(['data-url' => fn (SecureFile $record) => $record->getDownloadUrl()]),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListSecureFiles::route('/'),
            'create' => Pages\CreateSecureFile::route('/create'),
            'edit' => Pages\EditSecureFile::route('/{record}/edit'),
        ];
    }
}

