<?php

namespace Tests\Support;

use Illuminate\Testing\Fluent\AssertableJson;

trait AssertsApiEnvelope
{
    protected function assertApiEnvelope($response, string $pathInData = null): void
    {
        $response->assertOk()->assertJson(fn (AssertableJson $json) =>
        $json->has('data')
            ->has('meta')
            ->whereType('meta.timestamp', 'string')
            ->when($pathInData, fn ($j) => $j->has("data.$pathInData"))
        );
    }
}
