<?php

namespace App\Filament\Resources\SecureFileResource\Pages;

use App\Filament\Resources\SecureFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSecureFile extends EditRecord
{
    protected static string $resource = SecureFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle file upload and set file_path
        if (isset($data['file'])) {
            $data['file_path'] = $data['file'];
            unset($data['file']);
        }

        return $data;
    }
}

