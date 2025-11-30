<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;

class TenantRegistration extends RegisterTenant
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.tenancy.register-company';

    public static function getLabel(): string
    {
        return 'Register company';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Company Name')
                            ->placeholder('Acme Studio, LLC')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('address')
                            ->label('Business Address')
                            ->placeholder("123 Market St\nSan Francisco, CA 94103")
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Primary Contact')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Name')
                            ->placeholder('Jordan Lee')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->placeholder('jordan@acme.com')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    protected function handleRegistration(array $data): Tenant
    {
        $tenant = Tenant::create($data);

        $tenant->users()->attach(auth()->user());

        return $tenant;
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
