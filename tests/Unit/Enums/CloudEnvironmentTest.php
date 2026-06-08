<?php

use InterWorks\PowerBI\Enums\CloudEnvironment;

dataset('cloud environments', [
    'commercial' => [
        CloudEnvironment::Commercial,
        'https://api.powerbi.com/v1.0/myorg',
        'https://login.microsoftonline.com',
        'https://analysis.windows.net/powerbi/api',
    ],
    'gcc' => [
        CloudEnvironment::GCC,
        'https://api.powerbigov.us/v1.0/myorg',
        'https://login.microsoftonline.com',
        'https://analysis.usgovcloudapi.net/powerbi/api',
    ],
    'gcc_high' => [
        CloudEnvironment::GCCHigh,
        'https://api.high.powerbigov.us/v1.0/myorg',
        'https://login.microsoftonline.us',
        'https://high.analysis.usgovcloudapi.net/powerbi/api',
    ],
    'dod' => [
        CloudEnvironment::DoD,
        'https://api.mil.powerbigov.us/v1.0/myorg',
        'https://login.microsoftonline.us',
        'https://mil.analysis.usgovcloudapi.net/powerbi/api',
    ],
]);

test('resolves URLs per environment', function (
    CloudEnvironment $environment,
    string $baseUrl,
    string $authority,
    string $resourceUrl
) {
    expect($environment->baseUrl())->toBe($baseUrl);
    expect($environment->authorizeEndpoint('tenant-1'))->toBe("{$authority}/tenant-1/oauth2/authorize");
    expect($environment->tokenEndpoint('tenant-1'))->toBe("{$authority}/tenant-1/oauth2/token");
    expect($environment->resourceUrl())->toBe($resourceUrl);
})->with('cloud environments');

test('fromString resolves supported values', function () {
    expect(CloudEnvironment::fromString('commercial'))->toBe(CloudEnvironment::Commercial);
    expect(CloudEnvironment::fromString('gcc'))->toBe(CloudEnvironment::GCC);
    expect(CloudEnvironment::fromString('gcc_high'))->toBe(CloudEnvironment::GCCHigh);
    expect(CloudEnvironment::fromString('dod'))->toBe(CloudEnvironment::DoD);
});

test('fromString defaults to commercial for null or empty values', function () {
    expect(CloudEnvironment::fromString(null))->toBe(CloudEnvironment::Commercial);
    expect(CloudEnvironment::fromString(''))->toBe(CloudEnvironment::Commercial);
});

test('fromString throws for unrecognized values', function (string $invalid) {
    expect(fn () => CloudEnvironment::fromString($invalid))
        ->toThrow(InvalidArgumentException::class, "Invalid Power BI cloud environment: {$invalid}");
})->with([
    'typo with hyphen' => 'gcc-high',
    'wrong case' => 'GCC',
    'unknown value' => 'mars',
]);

test('fromString lists the supported values in the exception message', function () {
    expect(fn () => CloudEnvironment::fromString('mars'))
        ->toThrow(InvalidArgumentException::class, 'Supported values: commercial, gcc, gcc_high, dod.');
});

test('endpoint URLs interpolate the tenant verbatim', function () {
    // Tenant validation is the connector layer's concern; the enum builds URLs as given.
    expect(CloudEnvironment::Commercial->authorizeEndpoint(''))
        ->toBe('https://login.microsoftonline.com//oauth2/authorize');
    expect(CloudEnvironment::Commercial->tokenEndpoint(''))
        ->toBe('https://login.microsoftonline.com//oauth2/token');
});
