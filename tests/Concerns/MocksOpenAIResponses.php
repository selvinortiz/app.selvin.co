<?php

namespace Tests\Concerns;

use Mockery;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

trait MocksOpenAIResponses
{
    protected function mockOpenAiTextResponse(string $text): void
    {
        $response = new class($text)
        {
            public function __construct(
                public string $outputText,
            ) {
            }

            public function toArray(): array
            {
                return ['output_text' => $this->outputText];
            }
        };

        $responses = Mockery::mock();
        $responses
            ->shouldReceive('create')
            ->andReturn($response);

        $client = Mockery::mock();
        $client
            ->shouldReceive('responses')
            ->andReturn($responses);

        OpenAI::swap($client);
    }

    protected function mockOpenAiFailure(?Throwable $throwable = null): void
    {
        $responses = Mockery::mock();
        $responses
            ->shouldReceive('create')
            ->andThrow($throwable ?? new \RuntimeException('OpenAI unavailable in test.'));

        $client = Mockery::mock();
        $client
            ->shouldReceive('responses')
            ->andReturn($responses);

        OpenAI::swap($client);
    }
}
