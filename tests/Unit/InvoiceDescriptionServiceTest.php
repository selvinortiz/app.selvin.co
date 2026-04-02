<?php

namespace Tests\Unit;

use App\Services\InvoiceDescriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\MocksOpenAIResponses;
use Tests\TestCase;

class InvoiceDescriptionServiceTest extends TestCase
{
    use MocksOpenAIResponses;
    use RefreshDatabase;

    public function test_it_keeps_identical_descriptions_at_different_rates_as_separate_line_items(): void
    {
        $this->mockOpenAiFailure();

        $hours = new Collection([
            (object) [
                'date' => '2026-02-10',
                'description' => 'Shared task',
                'hours' => 1.50,
                'rate' => 150.00,
            ],
            (object) [
                'date' => '2026-03-10',
                'description' => 'Shared task',
                'hours' => 2.00,
                'rate' => 175.00,
            ],
        ]);

        $details = InvoiceDescriptionService::generate(
            $hours,
            Carbon::parse('2026-04-01'),
            'February – March 2026'
        );

        $this->assertSame(575.0, $details['amount']);
        $this->assertArrayNotHasKey('summary', $details);
        $this->assertStringContainsString(
            '- Shared task (1.50 hours @ $150.00/hr) = $225.00',
            $details['description']
        );
        $this->assertStringContainsString(
            '- Shared task (2.00 hours @ $175.00/hr) = $350.00',
            $details['description']
        );
        $this->assertStringContainsString('Total Hours: 3.50', $details['description']);
    }

    public function test_it_normalizes_ai_output_to_use_exact_decimal_hours(): void
    {
        $this->mockOpenAiTextResponse("Work this period focused on catch-up work.\n\nTotal Hours (9)");

        $hours = new Collection([
            (object) [
                'date' => '2026-02-10',
                'description' => 'Shared task',
                'hours' => 6.25,
                'rate' => 150.00,
            ],
            (object) [
                'date' => '2026-03-10',
                'description' => 'Another task',
                'hours' => 2.25,
                'rate' => 150.00,
            ],
        ]);

        $details = InvoiceDescriptionService::generate(
            $hours,
            Carbon::parse('2026-04-01'),
            'February – March 2026'
        );

        $this->assertSame(
            "Work this period focused on catch-up work.\n\nTotal Hours (8.50)",
            $details['description']
        );
    }
}
