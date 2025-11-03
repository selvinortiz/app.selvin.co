<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class InvoiceDescriptionService
{
    public static function generate(Collection $hours, Carbon $date): array
    {
        if ($hours->isEmpty()) {
            return [
                'amount' => 0,
                'summary' => '',
                'description' => '',
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

        $description = '';

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

        $generated = [
            'amount' => $totalAmount,
            'summary' => static::generateSummary($description),
            'description' => $description,
        ];

        Log::info('Description: ', compact('generated'));

        return $generated;
    }

    public static function generateSummary(string $description): string {
        $response = OpenAI::responses()->create([
            'model' => 'gpt-5-mini',
            'input' => implode(PHP_EOL, [
                'Take on the role of a senior software developer with great communication skills and experience writing technical documents.',
                '',
                'Your goal is to generate a clear, succinct, and well-written invoice summary that communicates the value or benefit of the work performed without all the buzzwords, preamble or unnecessary details.',
                '',
                'The invoice summary should be based on the billable time entries provided below.',
                '',
                'For additional context, below is also an example of time entries and a good invoice summary generated from them.',
                '',
                '---',
                'EXAMPLE TIME ENTRIES:',
                '- Mutual alignment meetings (3.00 hours @ $150.00/hr) = $450.00',
                '- Initial research and work to integrate Resi Pixel with Resi Elements (1.00 hours @ $150.00/hr) = $150.00',
                '- Meeting with Grady and Brennen to go over Pixel updates, Gables, and roadmap. Fixed it by having James hides those from Yardi and re-run all the syncs. (1.00 hours @ $150.00/hr) = $150.00',
                '- Gables -> Troubleshooting an issue where some floor plans showed a price range starting from $1 (3.00 hours @ $150.00/hr) = $450.00',
                '- Worked on updating Resi Elements with support for Resi Pixel and began updating the Resi App to support for lead source (8.00 hours @ $150.00/hr) = $1,200.00',
                '- Pixel -> Begin modeling the updated schema for lead sources in accordance with the new Resi Pixel v1.5 API and the Resi Elements plugin implementation (2.00 hours @ $150.00/hr) = $300.00',
                '- Gables -> Audited the codebase to figure out why search with city/state were not returning any results. It turned out to be an algolia configuration issue that I fixed by updating the searchable attributes and manually running the syncs. (3.00 hours @ $150.00/hr) = $450.00',
                '- Pixel -> Fixed a few bugs in the JS lib and began modeling the Filament UI for lead sources and integrating the the Elements plugin (3.00 hours @ $150.00/hr) = $450.00',
                '- Gables -> Prepared and deployed all the updates to sync and website to production for improved search (1.00 hours @ $150.00/hr) = $150.00',
                '- Gables -> Review, prepare and deploy open in google maps feature with full address instead of partial (1.00 hours @ $150.00/hr) = $150.00',
                '- Gables -> Update content security policy to allow reCAPTCHA domains and headers for AI agent integration from Paradox (1.00 hours @ $150.00/hr) = $150.00',
                '- Create lead source schema, models and initial UI. Wire up lead attribution when processing a lead and scaffold Filament UI (4.00 hours @ $150.00/hr) = $600.00',
                '- Gables -> Fixed navigation for embedded engrain maps launched from the floor plan available unit rows. (6.00 hours @ $150.00/hr) = $900.00',
                '- Gables -> Prepared, deployed, and tested embedded back navigation updates (1.00 hours @ $150.00/hr) = $150.00',
                '',
                'Total Hours: 35.00',
                'Total Amount: $5250.00',
                '',
                'EXAMPLE INVOICE SUMMARY:',
                'Work this month focused on continued development across Pixel, Elements, V2 and Gables. On Pixel, Elements and V2, efforts centered on schema modeling, lead source attribution, and Filament UI implementation for v1.5 integration. For Gables, multiple production fixes and feature improvements were completed, including resolving pricing display issues, refining search configurations in Algolia, updating content security policies, and deploying new navigation and mapping features.',
                '',
                'Total Hours (35)',
                '',
                '---',
                'RULES:',
                '- Always start the invoice summary with: "Work this month focused on"',
                '- Always end with Total Hours (X) in its own following a blank line.',
                '- Always make sure the invoice summary is a single paragraph of 600 characters max.',
                '- Avoid using "I", as in "I did X or Y". Instead, just write what actually got done or what the outcome, benefit achieved.',
                '',
                '---',
                'REAL TIME ENTRIES:',
                ...explode(PHP_EOL, $description),
                'REAL INVOICE SUMMARY:',
                ''
            ]),
        ]);

        $summary = $response->outputText;

        return $summary ?? $description;
    }
}
