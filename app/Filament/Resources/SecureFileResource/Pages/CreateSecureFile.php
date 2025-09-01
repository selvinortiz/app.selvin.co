<?php

namespace App\Filament\Resources\SecureFileResource\Pages;

use App\Filament\Resources\SecureFileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSecureFile extends CreateRecord
{
    protected static string $resource = SecureFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle file upload and set file_path
        if (isset($data['file'])) {
            $data['file_path'] = $data['file'];
            unset($data['file']);
        }

        return $data;
    }
}

