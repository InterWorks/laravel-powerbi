<?php

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\Enums\CloudEnvironment;
use InterWorks\PowerBI\Enums\ConnectionAccountType;

test('can create PowerBIServicePrincipal with Service Principal account type', function () {
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        connectionAccountType: ConnectionAccountType::ServicePrincipal
    );

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);
});

test('can create PowerBIServicePrincipal with Service Principal Admin account type', function () {
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-admin-client-id',
        clientSecret: 'test-admin-client-secret',
        connectionAccountType: ConnectionAccountType::AdminServicePrincipal
    );

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::AdminServicePrincipal);
});

test('throws exception when creating PowerBIServicePrincipal with AzureUser account type', function () {
    expect(fn () => new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        connectionAccountType: ConnectionAccountType::AzureUser
    ))->toThrow(
        InvalidArgumentException::class,
        'PowerBIServicePrincipal connector cannot be used with AzureUser account type'
    );
});

test('resolves correct base URL', function () {
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    expect($connector->resolveBaseUrl())->toBe('https://api.powerbi.com/v1.0/myorg');
});

test('defaults to commercial cloud environment when not specified', function () {
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    expect($connector->getCloudEnvironment())->toBe(CloudEnvironment::Commercial);
    expect($connector->resolveBaseUrl())->toBe('https://api.powerbi.com/v1.0/myorg');
});

test('resolves GCC base URL when constructed with the GCC environment', function () {
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cloudEnvironment: 'gcc'
    );

    expect($connector->getCloudEnvironment())->toBe(CloudEnvironment::GCC);
    expect($connector->resolveBaseUrl())->toBe('https://api.powerbigov.us/v1.0/myorg');
});

test('throws for an unknown cloud environment', function () {
    expect(fn () => new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cloudEnvironment: 'mars-cloud'
    ))->toThrow(InvalidArgumentException::class, 'Invalid Power BI cloud environment: mars-cloud');
});

test('reads cloud environment from config when not explicitly passed', function () {
    Config::set('powerbi.cloud_environment', 'dod');
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    expect($connector->getCloudEnvironment())->toBe(CloudEnvironment::DoD);
    expect($connector->resolveBaseUrl())->toBe('https://api.mil.powerbigov.us/v1.0/myorg');
});

test('resolves the token endpoint and resource URL per cloud environment', function (
    ?string $cloudEnvironment,
    string $expectedTokenEndpoint,
    string $expectedResourceUrl
) {
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cloudEnvironment: $cloudEnvironment
    );

    // Use reflection to pin the private endpoint resolution (guards the commercial
    // login.windows.net -> login.microsoftonline.com host change and gov routing)
    $reflection = new ReflectionClass($connector);

    $tokenEndpoint = $reflection->getMethod('getTokenEndpoint');
    $tokenEndpoint->setAccessible(true);
    expect($tokenEndpoint->invoke($connector))->toBe($expectedTokenEndpoint);

    $resourceUrl = $reflection->getMethod('getResourceUrl');
    $resourceUrl->setAccessible(true);
    expect($resourceUrl->invoke($connector))->toBe($expectedResourceUrl);
})->with([
    'commercial (default)' => [
        null,
        'https://login.microsoftonline.com/test-tenant/oauth2/token',
        'https://analysis.windows.net/powerbi/api',
    ],
    'gcc' => [
        'gcc',
        'https://login.microsoftonline.com/test-tenant/oauth2/token',
        'https://analysis.usgovcloudapi.net/powerbi/api',
    ],
    'gcc_high' => [
        'gcc_high',
        'https://login.microsoftonline.us/test-tenant/oauth2/token',
        'https://high.analysis.usgovcloudapi.net/powerbi/api',
    ],
    'dod' => [
        'dod',
        'https://login.microsoftonline.us/test-tenant/oauth2/token',
        'https://mil.analysis.usgovcloudapi.net/powerbi/api',
    ],
]);

test('uses default Service Principal account type when not specified', function () {
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);
});

test('caching can be disabled via config', function () {
    Config::set('powerbi.cache.enabled', false);
    $connector = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    // Create a reflection class to access the protected cachingEnabled property
    $reflection = new ReflectionClass($connector);
    $isCachingEnabled = $reflection->getProperty('cachingEnabled');
    $isCachingEnabled->setAccessible(true);
    expect($isCachingEnabled->getValue($connector))->toBeFalse();
});
