<?php

namespace App\Providers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        DateTimePicker::configureUsing(fn(DateTimePicker $component) => $component->timezone(config('app.user_timezone')));
        TextColumn::configureUsing(fn(TextColumn $column) => $column->timezone(config('app.user_timezone')));
    }
}
