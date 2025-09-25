<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class InvoiceDescriptionService
{
    public static function generate(Collection $hours, Carbon $date): array
    {
        if ($hours->isEmpty()) {
            return [
                'amount' => 0,
                'description' => "No billable time entries found for {$date->format('F Y')}.",
            ];
        }

        $totalHours = $hours->sum('hours');
        $totalAmount = $hours->sum(fn ($entry) => $entry->hours * $entry->rate);

        // Group entries by description to consolidate similar work
        $groupedEntries = $hours->groupBy('description')->map(function ($entries) {
            return [
                'hours' => $entries->sum('hours'),
                'rate' => $entries->first()->rate,
                'amount' => $entries->sum(fn ($entry) => $entry->hours * $entry->rate),
            ];
        });

        $description = "Professional Services for {$date->format('F Y')}\n\n";

        foreach ($groupedEntries as $desc => $data) {
            $description .= sprintf(
                "- %s (%.2f hours @ $%.2f/hr) = $%.2f\n",
                $desc,
                $data['hours'],
                $data['rate'],
                $data['amount']
            );
        }

        $description .= sprintf(
            "\nTotal Hours: %.2f\nTotal Amount: $%.2f",
            $totalHours,
            $totalAmount
        );

        $summary = sprintf('Total Hours (%.2f)', $totalHours);

        return [
            'amount' => $totalAmount,
            'summary' => $summary,
            'description' => $description,
        ];
    }
}
