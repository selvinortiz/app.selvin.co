<?php

namespace App\Filament\Resources\ContractorResource\RelationManagers;

use App\Models\ContractorDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';
    protected static ?string $title = 'Documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('document_type')
                    ->label('Document Type')
                    ->options([
                        'contractor_agreement' => 'Contractor Agreement',
                        'w8ben' => 'Form W-8BEN',
                        'w9' => 'Form W-9',
                        '1099' => 'Form 1099',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->default('other')
                    ->native(false),

                Forms\Components\TextInput::make('name')
                    ->label('Document Name / Description')
                    ->helperText('Optional: Add a descriptive name for this document')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('file_path')
                    ->label('Document File')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(20480) // 20MB
                    ->disk('s3')
                    ->directory('contractor/documents')
                    ->visibility('private')
                    ->helperText('Maximum file size: 20MB. Only PDF files are accepted.')
                    ->downloadable()
                    ->previewable()
                    ->openable()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ContractorDocument $record) => $record->document_type_label)
                    ->color(fn (string $state): string => match ($state) {
                        'contractor_agreement' => 'success',
                        'w8ben', 'w9' => 'info',
                        '1099' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->placeholder('—')
                    ->default('—'),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('File')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (ContractorDocument $record) => $record->file_name),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 2) . ' KB' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Document Type')
                    ->options([
                        'contractor_agreement' => 'Contractor Agreement',
                        'w8ben' => 'Form W-8BEN',
                        'w9' => 'Form W-9',
                        '1099' => 'Form 1099',
                        'other' => 'Other',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $data['tenant_id'] = $livewire->getOwnerRecord()->tenant_id;
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->action(function (ContractorDocument $record) {
                        if ($record->file_path && Storage::disk('s3')->exists($record->file_path)) {
                            $url = Storage::disk('s3')->temporaryUrl($record->file_path, now()->addMinutes(5));
                            return redirect($url);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('File not found')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (ContractorDocument $record) => !empty($record->file_path)),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (ContractorDocument $record) {
                        if ($record->file_path && Storage::disk('s3')->exists($record->file_path)) {
                            $file = Storage::disk('s3')->get($record->file_path);
                            $fileName = $record->file_name ?? basename($record->file_path);

                            return response()->streamDownload(function () use ($file) {
                                echo $file;
                            }, $fileName, [
                                'Content-Type' => $record->mime_type ?? 'application/pdf',
                            ]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('File not found')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (ContractorDocument $record) => !empty($record->file_path)),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (ContractorDocument $record) {
                        // Delete the file from S3 when the record is deleted
                        if ($record->file_path && Storage::disk('s3')->exists($record->file_path)) {
                            Storage::disk('s3')->delete($record->file_path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Delete files from S3 when records are deleted
                            foreach ($records as $record) {
                                if ($record->file_path && Storage::disk('s3')->exists($record->file_path)) {
                                    Storage::disk('s3')->delete($record->file_path);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
