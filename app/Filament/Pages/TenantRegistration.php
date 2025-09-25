<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;

class TenantRegistration extends RegisterTenant
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getLabel(): string
    {
        return 'Register company';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('location')
                    ->maxLength(255),
                Select::make('timezone')
                    ->options(function () {
                        return collect(timezone_identifiers_list())
                            ->mapWithKeys(fn ($timezone) => [$timezone => $timezone]);
                    })
                    ->searchable(),
            ]);
    }

    protected function handleRegistration(array $data): Tenant
    {
        $tenant = Tenant::create($data);

        $tenant->users()->attach(auth()->user());

        return $tenant;
    }
}
