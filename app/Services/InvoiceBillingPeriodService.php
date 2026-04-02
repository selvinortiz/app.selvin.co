<?php

namespace App\Services;

use App\Exceptions\NonContiguousBillingPeriodException;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InvoiceBillingPeriodService
{
    public static function normalizeMonthKeys(array $billingMonths): array
    {
        $normalized = collect($billingMonths)
            ->filter(fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($normalized === []) {
            throw new NonContiguousBillingPeriodException('Select at least one billing month.');
        }

        static::assertContiguousMonthKeys($normalized);

        return $normalized;
    }

    public static function assertContiguousMonthKeys(array $billingMonths): void
    {
        $previous = null;

        foreach ($billingMonths as $billingMonth) {
            $current = Carbon::createFromFormat('Y-m', $billingMonth)->startOfMonth();

            if ($previous !== null && !$previous->copy()->addMonth()->equalTo($current)) {
                throw new NonContiguousBillingPeriodException(
                    'Billing months must form a contiguous range without gaps.'
                );
            }

            $previous = $current;
        }
    }

    public static function fromMonthKeys(array $billingMonths): array
    {
        $normalized = static::normalizeMonthKeys($billingMonths);

        return [
            Carbon::createFromFormat('Y-m', $normalized[0])->startOfMonth(),
            Carbon::createFromFormat('Y-m', $normalized[array_key_last($normalized)])->startOfMonth(),
        ];
    }

    public static function fromHours(Collection $hours, Carbon $fallbackDate): array
    {
        if ($hours->isEmpty()) {
            $fallbackMonth = $fallbackDate->copy()->startOfMonth();

            return [$fallbackMonth, $fallbackMonth->copy()];
        }

        return static::fromMonthKeys(static::monthKeysFromHours($hours));
    }

    public static function monthKeysFromHours(Collection $hours): array
    {
        return $hours
            ->map(fn ($hour) => Carbon::parse($hour->date)->startOfMonth()->format('Y-m'))
            ->unique()
            ->sort()
            ->values()
            ->pipe(function (Collection $months): array {
                $values = $months->all();

                static::assertContiguousMonthKeys($values);

                return $values;
            });
    }

    public static function buildLabel(Carbon $start, ?Carbon $end = null): string
    {
        $end ??= $start;
        $start = $start->copy()->startOfMonth();
        $end = $end->copy()->startOfMonth();

        if ($start->equalTo($end)) {
            return $start->format('F Y');
        }

        if ($start->year === $end->year) {
            return $start->format('F') . ' – ' . $end->format('F Y');
        }

        return $start->format('F Y') . ' – ' . $end->format('F Y');
    }

    public static function buildReference(Carbon $start, ?Carbon $end = null): string
    {
        $end ??= $start;
        $start = $start->copy()->startOfMonth();
        $end = $end->copy()->startOfMonth();

        $startAbbr = strtoupper($start->format('M'));
        $endAbbr = strtoupper($end->format('M'));

        if ($start->equalTo($end)) {
            return "TL{$startAbbr}{$start->format('Y')}";
        }

        if ($start->year === $end->year) {
            return "TL{$startAbbr}{$endAbbr}{$end->format('Y')}";
        }

        return "TL{$startAbbr}{$start->format('Y')}{$endAbbr}{$end->format('Y')}";
    }

    public static function resolveForInvoice(Invoice $invoice): array
    {
        $start = $invoice->billing_period_start?->copy()->startOfMonth();
        $end = $invoice->billing_period_end?->copy()->startOfMonth();

        if ($start && $end) {
            return [$start, $end];
        }

        if ($invoice->relationLoaded('hours')) {
            return static::fromHours($invoice->hours, $invoice->date->copy());
        }

        return static::fromHours($invoice->hours()->get(['date']), $invoice->date->copy());
    }
}
