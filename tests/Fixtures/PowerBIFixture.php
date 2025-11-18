<?php

namespace InterWorks\PowerBI\Tests\Fixtures;

use Saloon\Http\Faking\Fixture;

use function Pest\Faker\fake;

class PowerBIFixture extends Fixture
{
    protected function defineName(): string
    {
        return 'power_bi';
    }

    protected function defineSensitiveHeaders(): array
    {
        return [
            'Authorization' => 'REDACTED',
            'RequestId' => 'REDACTED',
        ];
    }

    protected function defineSensitiveJsonParameters(): array
    {
        return [
            'token' => 'REDACTED_TOKEN',
            'tokenId' => fn () => fake()->uuid,
            'id' => fn () => fake()->uuid,
            'clientId' => fn () => fake()->uuid,
            'datasetId' => fn () => fake()->uuid,
            'datasetWorkspaceId' => fn () => fake()->uuid,
            'webUrl' => 'REDACTED_WEB_URL',
            'embedUrl' => 'REDACTED_EMBED_URL',
        ];
    }

    protected function defineSensitiveRegexPatterns(): array
    {
        return [
            // Make sure to remove group IDs from the context urls
            "/myorg\\\\\/groups\\\\\/[\S]+\\\\\//" => "myorg\/groups\/[REDACTED-GROUP-ID]\/",
        ];
    }
}
