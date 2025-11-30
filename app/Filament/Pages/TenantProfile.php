<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;
use Illuminate\Support\Str;

class TenantProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Company profile';
    }

    protected function getRedirectUrl(): ?string
    {
        $tenant = \Filament\Facades\Filament::getTenant();

        return $tenant
            ? route(static::getRouteName(), ['tenant' => $tenant])
            : null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Business Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('tax_id')
                            ->label('Tax ID (ITIN/EIN)')
                            ->helperText('EIN or ITIN')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Primary Contact')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Contact Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_title')
                            ->label('Title')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Branding')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Company Logo')
                            ->image()
                            ->disk('s3')
                            ->directory('tenant/logos')
                            ->visibility('private')
                            ->maxSize(5120) // 5MB
                            ->helperText('Recommended: Square or horizontal logo, max 5MB. Will appear in panel header.')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null, // Free aspect ratio
                                '1:1', // Square
                                '16:9', // Horizontal
                            ])
                            ->getUploadedFileNameForStorageUsing(function ($file, Forms\Components\FileUpload $component) {
                                $extension = $file->getClientOriginalExtension();
                                $uuid = Str::uuid();
                                $tenantId = $component->getRecord()?->id ?? 'temp';

                                return "{$tenantId}-{$uuid}.{$extension}";
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('brand_color')
                            ->label('Brand Color')
                            ->options(Tenant::brandColorOptions())
                            ->searchable()
                            ->helperText('Primary color for the panel interface')
                            ->default('Purple'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
