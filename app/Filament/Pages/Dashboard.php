<?php

namespace App\Filament\Pages;

use App\Services\MonthContextService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action as PageAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;

class Dashboard extends BaseDashboard implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'selected_month' => MonthContextService::getSelectedMonth()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('selected_month')
                    ->label('Viewing Month')
                    ->displayFormat('F Y')
                    ->format('Y-m-d')
                    ->default(MonthContextService::getSelectedMonth()->toDateString())
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        if ($state) {
                            MonthContextService::setSelectedMonth(Carbon::parse($state));
                            $this->dispatch('month-context-updated');

                            Notification::make()
                                ->title('Month Context Updated')
                                ->body('All widgets and tables now show data for ' . MonthContextService::getFormattedMonth())
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
            PageAction::make('change_month')
                ->label(fn () => 'Viewing: ' . MonthContextService::getFormattedMonth())
                ->icon('heroicon-m-calendar')
                ->color(fn () => MonthContextService::isCustomMonthSelected() ? 'warning' : 'gray')
                ->form([
                    Section::make('Month Context')
                        ->description('Select the month for viewing data across all widgets and tables.')
                        ->schema([
                            DatePicker::make('selected_month')
                                ->label('Viewing Month')
                                ->displayFormat('F Y')
                                ->format('Y-m-d')
                                ->default(MonthContextService::getSelectedMonth()->toDateString())
                                ->required(),
                        ])
                        ->compact(),
                ])
                ->action(function (array $data) {
                    MonthContextService::setSelectedMonth(Carbon::parse($data['selected_month']));
                    $this->dispatch('month-context-updated');

                    Notification::make()
                        ->title('Month Context Updated')
                        ->body('All widgets and tables now show data for ' . MonthContextService::getFormattedMonth())
                        ->success()
                        ->send();
                })
                ->modalWidth('sm'),

            PageAction::make('reset_month')
                ->label('Reset to Current Month')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function () {
                    MonthContextService::resetToCurrentMonth();
                    $this->dispatch('month-context-updated');

                    Notification::make()
                        ->title('Reset to Current Month')
                        ->body('All widgets and tables now show data for ' . MonthContextService::getFormattedMonth())
                        ->success()
                        ->send();
                })
                ->visible(fn () => MonthContextService::isCustomMonthSelected()),
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DashboardHourStatsWidget::class,
            \App\Filament\Widgets\BillableHoursOverview::class,
            \App\Filament\Widgets\InvoiceStatusSummary::class,
            \App\Filament\Widgets\ContractorInvoiceSummary::class,
        ];
    }
}
