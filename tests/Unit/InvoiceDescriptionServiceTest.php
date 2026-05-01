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
        $this->assertFalse($details['used_ai']);
        $this->assertSame('OpenAI unavailable in test.', $details['fallback_reason']);
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
        $this->assertTrue($details['used_ai']);
        $this->assertNull($details['fallback_reason']);
    }

    public function test_it_bounds_the_openai_request_to_reduce_rate_limit_pressure(): void
    {
        $this->mockOpenAiTextResponse(
            "Work this month focused on delivery work.\n\nTotal Hours (1.00)",
            function (array $parameters): void {
                $this->assertSame('gpt-5-mini', $parameters['model']);
                $this->assertSame(['effort' => 'minimal'], $parameters['reasoning']);
                $this->assertSame(700, $parameters['max_output_tokens']);
                $this->assertFalse($parameters['store']);
            }
        );

        $hours = new Collection([
            (object) [
                'date' => '2026-04-10',
                'description' => 'Delivery work',
                'hours' => 1.00,
                'rate' => 150.00,
            ],
        ]);

        $details = InvoiceDescriptionService::generate(
            $hours,
            Carbon::parse('2026-04-01'),
            'April 2026'
        );

        $this->assertTrue($details['used_ai']);
    }

    public function test_it_summarizes_rate_limit_errors_for_display(): void
    {
        $this->assertSame(
            'OpenAI rate limit exceeded.',
            InvoiceDescriptionService::fallbackReasonForDisplay('Request rate limit has been exceeded.')
        );
    }
}
