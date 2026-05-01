<?php

namespace Tests\Concerns;

use Mockery;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

trait MocksOpenAIResponses
{
    protected function mockOpenAiTextResponse(string $text, ?callable $assertParameters = null): void
    {
        $response = new class($text)
        {
            public function __construct(
                public string $outputText,
            ) {}

            public function toArray(): array
            {
                return ['output_text' => $this->outputText];
            }
        };

        $responses = Mockery::mock();
        $responses
            ->shouldReceive('create')
            ->with(Mockery::on(function (array $parameters) use ($assertParameters): bool {
                if ($assertParameters !== null) {
                    $assertParameters($parameters);
                }

                return true;
            }))
            ->andReturn($response);

        $client = Mockery::mock();
        $client
            ->shouldReceive('responses')
            ->andReturn($responses);

        OpenAI::swap($client);
    }

    protected function mockOpenAiFailure(?Throwable $throwable = null, ?callable $assertParameters = null): void
    {
        $responses = Mockery::mock();
        $responses
            ->shouldReceive('create')
            ->with(Mockery::on(function (array $parameters) use ($assertParameters): bool {
                if ($assertParameters !== null) {
                    $assertParameters($parameters);
                }

                return true;
            }))
            ->andThrow($throwable ?? new \RuntimeException('OpenAI unavailable in test.'));

        $client = Mockery::mock();
        $client
            ->shouldReceive('responses')
            ->andReturn($responses);

        OpenAI::swap($client);
    }
}
