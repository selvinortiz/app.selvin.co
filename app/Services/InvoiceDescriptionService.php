<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;

class InvoiceDescriptionService
{
    public static function generate(Collection $hours, Carbon $date): array
    {
        if ($hours->isEmpty()) {
            return [
                'amount' => 0,
                'summary' => '',
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

        $lineItems = '';

        foreach ($groupedEntries as $desc => $data) {
            $lineItems .= sprintf(
                "- %s (%.2f hours @ $%.2f/hr) = $%.2f\n",
                $desc,
                $data['hours'],
                $data['rate'],
                $data['amount']
            );
        }

        $deterministicDescription = "Professional Services for {$date->format('F Y')}\n\n" .
            $lineItems .
            "\nTotal Hours: " . number_format($totalHours, 2) .
            "\nTotal Amount: $" . number_format($totalAmount, 2);

        Log::debug('InvoiceDescriptionService deterministic description built', [
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'line_items_length' => strlen($lineItems),
        ]);

        $aiDescription = static::generateAiDescription(
            trim($lineItems),
            $totalHours,
            $totalAmount,
            $date
        );

        Log::debug('InvoiceDescriptionService AI description result', [
            'used_ai' => $aiDescription !== null,
        ]);

        $finalDescription = $aiDescription ?: $deterministicDescription;

        $summary = sprintf('Total Hours (%.2f)', $totalHours);

        return [
            'amount' => $totalAmount,
            'summary' => $summary,
            'description' => $finalDescription,
        ];
    }

    protected static function generateAiDescription(
        string $lineItems,
        float $totalHours,
        float $totalAmount,
        Carbon $date
    ): ?string {
        if ($lineItems === '') {
            return null;
        }

        $prompt = implode("\n", [
            'You are a senior software consultant writing an invoice description for a client.',
            'Output short and clear paragraphs (combined max 600 characters) that starts with "Work this month focused on".',
            'Write in a confident, outcome-focused tone, and active style where context and value is highlighted.',
            'Highlight major workstreams, shipped releases, fixes, refactors, integrations, and testing.',
            'Avoid filler, avoid first-person, and avoid dollar amounts. Keep it concise and human.',
            'IMPORTANT: Avoid m-dashes, hyphens, lists, or buzzwords.',
            'End with a blank line followed by: Total Hours (' . number_format($totalHours, 0) . ')',
            'Use the billable time entries below as source material; do not invent work.',
            'For context only (do not mention money), total amount: $' . number_format($totalAmount, 2) . '.',
            'Month: ' . $date->format('F Y') . '.',
            'Billable time entries:',
            $lineItems,
            'Now write the description.',
        ]);

        $options = [];

        $timeout = config('openai.request_timeout', 0);

        if ($timeout) {
            $options['timeout'] = $timeout;
        }

        $attempts = 0;
        $maxAttempts = 2;
        $lastError = null;

        Log::debug('InvoiceDescriptionService AI request starting', [
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'model' => 'gpt-5-mini',
        ]);

        while ($attempts < $maxAttempts) {
            try {
                $response = OpenAI::responses()->create([
                    'model' => 'gpt-5-mini',
                    'input' => $prompt,
                ], $options);

                Log::info('Response', $response->toArray());
                $text = $response->outputText;

                Log::debug('InvoiceDescriptionService AI response parsed', [
                    'attempt' => $attempts + 1,
                    'text_preview' => Str::limit($text, 120),
                    'status' => $response->status ?? null,
                    'response_type' => is_object($response) ? get_class($response) : gettype($response),
                    'raw' => method_exists($response, 'toArray') ? $response->toArray() : null,
                ]);

                if ($text !== '') {
                    Log::debug('InvoiceDescriptionService AI succeeded', [
                        'attempt' => $attempts + 1,
                        'output_length' => strlen($text),
                    ]);
                    return $text;
                }

                Log::debug('InvoiceDescriptionService AI returned empty text', [
                    'attempt' => $attempts + 1,
                ]);
            } catch (\Throwable $e) {
                $lastError = $e;
                Log::warning('AI invoice description generation failed', [
                    'attempt' => $attempts + 1,
                    'error' => $e->getMessage(),
                ]);
            }

            $attempts++;
        }

        if ($lastError) {
            Log::error('AI invoice description generation exhausted retries', [
                'error' => $lastError->getMessage(),
            ]);
        }

        return null;
    }
}
