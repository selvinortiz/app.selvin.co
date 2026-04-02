<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenAI\Client as OpenAIClient;
use OpenAI\Contracts\ClientContract as OpenAIClientContract;
use OpenAI\Laravel\Facades\OpenAI;

class InvoiceDescriptionService
{
    public static function generate(Collection $hours, Carbon $date, ?string $periodLabel = null): array
    {
        $periodLabel = $periodLabel ?? $date->format('F Y');

        if ($hours->isEmpty()) {
            return [
                'amount' => 0,
                'description' => "No billable time entries found for {$periodLabel}.",
            ];
        }

        $totalHours = $hours->sum('hours');
        $totalAmount = $hours->sum(fn ($entry) => $entry->hours * $entry->rate);

        // Keep identical work at different rates split into separate line items.
        $groupedEntries = $hours->groupBy(
            fn ($entry) => $entry->description . '|' . number_format((float) $entry->rate, 2, '.', '')
        )->map(function ($entries) {
            return [
                'description' => $entries->first()->description,
                'hours' => $entries->sum('hours'),
                'rate' => $entries->first()->rate,
                'amount' => $entries->sum(fn ($entry) => $entry->hours * $entry->rate),
            ];
        });

        $lineItems = '';

        foreach ($groupedEntries as $data) {
            $lineItems .= sprintf(
                "- %s (%.2f hours @ $%.2f/hr) = $%.2f\n",
                $data['description'],
                $data['hours'],
                $data['rate'],
                $data['amount']
            );
        }

        $deterministicDescription = "Professional Services for {$periodLabel}\n\n" .
            $lineItems .
            "\nTotal Hours: " . number_format($totalHours, 2) .
            "\nTotal Amount: $" . number_format($totalAmount, 2);

        $aiDescription = static::generateAiDescription(
            trim($lineItems),
            $totalHours,
            $totalAmount,
            $periodLabel
        );

        $finalDescription = $aiDescription ?: $deterministicDescription;

        return [
            'amount' => $totalAmount,
            'description' => $finalDescription,
        ];
    }

    protected static function generateAiDescription(
        string $lineItems,
        float $totalHours,
        float $totalAmount,
        string $periodLabel
    ): ?string {
        if ($lineItems === '') {
            return null;
        }

        $isMultiMonth = str_contains($periodLabel, '–');
        $periodWord = $isMultiMonth ? 'period' : 'month';
        $opener = $isMultiMonth ? "Work this period focused on" : "Work this month focused on";

        $prompt = implode("\n", [
            'You are a senior software consultant writing an invoice description for a client.',
            "Output short and clear paragraphs (combined max 600 characters) that starts with \"{$opener}\".",
            'Write in a confident, outcome-focused tone, and active style where context and value is highlighted.',
            'Highlight major workstreams, shipped releases, fixes, refactors, integrations, and testing.',
            'Avoid filler, avoid first-person, and avoid dollar amounts. Keep it concise and human.',
            'IMPORTANT: Avoid m-dashes, hyphens, lists, or buzzwords.',
            'End with a blank line followed by: Total Hours (' . number_format($totalHours, 2, '.', '') . ')',
            'Use the billable time entries below as source material; do not invent work.',
            'For context only (do not mention money), total amount: $' . number_format($totalAmount, 2) . '.',
            "Billing {$periodWord}: {$periodLabel}.",
            'Billable time entries:',
            $lineItems,
            'Now write the description.',
        ]);

        $previousMaxExecutionTime = static::setUnlimitedExecutionTime();
        $previousOpenAiTimeout = static::configureOpenAiTimeout(0);

        try {
            $response = OpenAI::responses()->create([
                'model' => 'gpt-5-mini',
                'input' => $prompt,
            ]);

            $text = $response->outputText;

            if ($text !== '') {
                return static::normalizeAiDescription($text, $totalHours);
            }
        } catch (\Throwable $e) {
            Log::warning('AI invoice description generation failed', [
                'timeout' => 'none',
                'error' => $e->getMessage(),
            ]);
            Log::error('AI invoice description generation fell back to deterministic output', [
                'timeout' => 'none',
                'error' => $e->getMessage(),
            ]);
        } finally {
            static::restoreExecutionTimeLimit($previousMaxExecutionTime);
            static::restoreOpenAiTimeout($previousOpenAiTimeout);
        }

        return null;
    }

    protected static function setUnlimitedExecutionTime(): int
    {
        $previousMaxExecutionTime = (int) ini_get('max_execution_time');

        if (! app()->runningUnitTests()) {
            @set_time_limit(0);
        }

        return $previousMaxExecutionTime;
    }

    protected static function restoreExecutionTimeLimit(int $previousMaxExecutionTime): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        if ($previousMaxExecutionTime > 0) {
            @set_time_limit($previousMaxExecutionTime);
        }
    }

    protected static function configureOpenAiTimeout(int|string|null $timeout): mixed
    {
        $previousTimeout = config('openai.request_timeout');

        if (app()->runningUnitTests()) {
            return $previousTimeout;
        }

        config(['openai.request_timeout' => $timeout]);

        app()->forgetInstance('openai');
        app()->forgetInstance(OpenAIClientContract::class);
        app()->forgetInstance(OpenAIClient::class);
        OpenAI::clearResolvedInstance('openai');

        return $previousTimeout;
    }

    protected static function restoreOpenAiTimeout(mixed $previousTimeout): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        config(['openai.request_timeout' => $previousTimeout]);

        app()->forgetInstance('openai');
        app()->forgetInstance(OpenAIClientContract::class);
        app()->forgetInstance(OpenAIClient::class);
        OpenAI::clearResolvedInstance('openai');
    }

    protected static function normalizeAiDescription(string $text, float $totalHours): string
    {
        $text = trim($text);
        $totalHoursLine = 'Total Hours (' . number_format($totalHours, 2, '.', '') . ')';

        $text = preg_replace('/Total Hours \([^)]+\)\s*$/', $totalHoursLine, $text) ?? $text;

        if (!str_ends_with($text, $totalHoursLine)) {
            $text .= "\n\n{$totalHoursLine}";
        }

        return $text;
    }
}
