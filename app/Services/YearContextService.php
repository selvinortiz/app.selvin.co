<?php

namespace App\Services;

class YearContextService
{
    const SESSION_KEY = 'selected_year';

    /**
     * Get the currently selected year from session or default to current year
     */
    public static function getSelectedYear(): int
    {
        $selectedYear = session(self::SESSION_KEY);

        if ($selectedYear) {
            return (int) $selectedYear;
        }

        return (int) now(config('app.user_timezone', 'UTC'))->year;
    }

    /**
     * Set the selected year in session
     */
    public static function setSelectedYear(int $year): void
    {
        session([self::SESSION_KEY => $year]);
    }

    /**
     * Reset to current year
     */
    public static function resetToCurrentYear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Check if a custom year is selected (not current year)
     */
    public static function isCustomYearSelected(): bool
    {
        $selectedYear = self::getSelectedYear();
        $currentYear = (int) now(config('app.user_timezone', 'UTC'))->year;

        return $selectedYear !== $currentYear;
    }

    /**
     * Get formatted year string for display
     */
    public static function getFormattedYear(): string
    {
        return (string) self::getSelectedYear();
    }

    /**
     * Get available years for selection (current year and past years with data)
     */
    public static function getAvailableYears(): array
    {
        $currentYear = (int) now(config('app.user_timezone', 'UTC'))->year;
        $years = [];

        // Include current year and past 5 years
        for ($i = 0; $i <= 5; $i++) {
            $year = $currentYear - $i;
            $years[$year] = (string) $year;
        }

        return $years;
    }
}
