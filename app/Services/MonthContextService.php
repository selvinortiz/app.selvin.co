<?php

namespace App\Services;

use Carbon\Carbon;

class MonthContextService
{
    const SESSION_KEY = 'selected_month';

    /**
     * Get the currently selected month from session or default to current month
     */
    public static function getSelectedMonth(): Carbon
    {
        $selectedMonth = session(self::SESSION_KEY);

        if ($selectedMonth) {
            return Carbon::parse($selectedMonth);
        }

        return now(config('app.user_timezone', 'UTC'))->startOfMonth();
    }

    /**
     * Set the selected month in session
     */
    public static function setSelectedMonth(Carbon $date): void
    {
        session([self::SESSION_KEY => $date->startOfMonth()->toDateString()]);
    }

    /**
     * Reset to current month
     */
    public static function resetToCurrentMonth(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Check if a custom month is selected (not current month)
     */
    public static function isCustomMonthSelected(): bool
    {
        $selectedMonth = self::getSelectedMonth();
        $currentMonth = now(config('app.user_timezone', 'UTC'))->startOfMonth();

        return !$selectedMonth->isSameMonth($currentMonth);
    }

    /**
     * Get formatted month string for display
     */
    public static function getFormattedMonth(): string
    {
        return self::getSelectedMonth()->format('F Y');
    }
}
