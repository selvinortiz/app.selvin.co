<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class TenantProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Company profile';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('location'),
                Select::make('timezone')
                    ->options(function () {
                        return collect(timezone_identifiers_list())
                            ->mapWithKeys(fn ($timezone) => [$timezone => $timezone]);
                    })
                    ->searchable(),
            ]);
    }
}
