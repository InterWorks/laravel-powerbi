<?php

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Classes\PowerBIConnectorBase;
use InterWorks\PowerBI\Connectors\PowerBIAzureUser;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\Requests\Groups\GetGroups;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    Config::set('cache.default', 'array');
    $this->mockClient = new MockClient([
        GetGroups::class => MockResponse::make(['@odata.count' => 0, 'value' => []]),
    ]);
});

test('serves a repeat request from the cache after the clock moves to a later second', function (PowerBIConnectorBase $connector) {
    $connector->withMockClient($this->mockClient);

    expect($connector->send(new GetGroups)->isCached())->toBeFalse();

    sleep(1);

    expect($connector->send(new GetGroups)->isCached())->toBeTrue();
    $this->mockClient->assertSentCount(1);
})->with([
    'service principal' => fn () => new PowerBIServicePrincipal('tenant', 'client', 'secret'),
    'azure user' => fn () => new PowerBIAzureUser('tenant', 'client', 'secret', 'https://example.com/callback'),
]);

test('does not share a cached response between access tokens', function () {
    $connector = (new PowerBIServicePrincipal('tenant', 'client', 'secret'))->withMockClient($this->mockClient);

    $connector->authenticate(new TokenAuthenticator('first-token'));
    expect($connector->send(new GetGroups)->isCached())->toBeFalse();

    $connector->authenticate(new TokenAuthenticator('second-token'));
    expect($connector->send(new GetGroups)->isCached())->toBeFalse();
    $this->mockClient->assertSentCount(2);
});

test('does not read the cache when caching is disabled', function () {
    Config::set('powerbi.cache.enabled', false);
    $connector = (new PowerBIServicePrincipal('tenant', 'client', 'secret'))->withMockClient($this->mockClient);

    $connector->send(new GetGroups);

    expect($connector->send(new GetGroups)->isCached())->toBeFalse();
    $this->mockClient->assertSentCount(2);
});

test('does not cache a failed response', function () {
    $mockClient = new MockClient([
        MockResponse::make(['error' => 'unavailable'], 500),
        MockResponse::make(['@odata.count' => 0, 'value' => []]),
    ]);
    $connector = (new PowerBIServicePrincipal('tenant', 'client', 'secret'))->withMockClient($mockClient);

    expect(fn () => $connector->send(new GetGroups))->toThrow(InternalServerErrorException::class);

    $response = $connector->send(new GetGroups);
    expect($response->isCached())->toBeFalse()
        ->and($response->status())->toBe(200);
});
