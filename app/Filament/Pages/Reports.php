<?php

namespace App\Filament\Pages;

use App\Services\YearContextService;
use Filament\Actions\Action as PageAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Reports extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?int $navigationSort = 31;
    protected static string $view = 'filament.pages.reports';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'selected_year' => YearContextService::getSelectedYear(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selected_year')
                    ->label('Year')
                    ->options(YearContextService::getAvailableYears())
                    ->default(YearContextService::getSelectedYear())
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        if ($state) {
                            YearContextService::setSelectedYear((int) $state);
                            $this->dispatch('year-context-updated');

                            Notification::make()
                                ->title('Year Context Updated')
                                ->body('All reports now show data for ' . YearContextService::getFormattedYear())
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('change_year')
                ->label(fn () => 'Viewing: ' . YearContextService::getFormattedYear())
                ->icon('heroicon-m-calendar')
                ->color(fn () => YearContextService::isCustomYearSelected() ? 'warning' : 'gray')
                ->form([
                    Section::make('Year Context')
                        ->description('Select the year for viewing YTD reports.')
                        ->schema([
                            Select::make('selected_year')
                                ->label('Year')
                                ->options(YearContextService::getAvailableYears())
                                ->default(YearContextService::getSelectedYear())
                                ->required(),
                        ])
                        ->compact(),
                ])
                ->action(function (array $data) {
                    YearContextService::setSelectedYear((int) $data['selected_year']);
                    $this->dispatch('year-context-updated');

                    Notification::make()
                        ->title('Year Context Updated')
                        ->body('All reports now show data for ' . YearContextService::getFormattedYear())
                        ->success()
                        ->send();
                })
                ->modalWidth('sm'),

            PageAction::make('reset_year')
                ->label('Reset to Current Year')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function () {
                    YearContextService::resetToCurrentYear();
                    $this->dispatch('year-context-updated');

                    Notification::make()
                        ->title('Reset to Current Year')
                        ->body('All reports now show data for ' . YearContextService::getFormattedYear())
                        ->success()
                        ->send();
                })
                ->visible(fn () => YearContextService::isCustomYearSelected()),
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\YTDRevenueStatsWidget::class,
            \App\Filament\Widgets\ClientInvoicingTableWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 12;
    }
}
