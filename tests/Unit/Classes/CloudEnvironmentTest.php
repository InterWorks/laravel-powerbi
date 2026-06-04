<?php

use InterWorks\PowerBI\Classes\CloudEnvironment;

test('resolves base URL per environment', function () {
    expect(CloudEnvironment::getBaseUrl(CloudEnvironment::COMMERCIAL))
        ->toBe('https://api.powerbi.com/v1.0/myorg');
    expect(CloudEnvironment::getBaseUrl(CloudEnvironment::GCC))
        ->toBe('https://api.powerbigov.us/v1.0/myorg');
    expect(CloudEnvironment::getBaseUrl(CloudEnvironment::GCC_HIGH))
        ->toBe('https://api.high.powerbigov.us/v1.0/myorg');
    expect(CloudEnvironment::getBaseUrl(CloudEnvironment::DOD))
        ->toBe('https://api.mil.powerbigov.us/v1.0/myorg');
});

test('resolves authorize endpoint per environment', function () {
    expect(CloudEnvironment::getAuthorizeEndpoint(CloudEnvironment::COMMERCIAL, 'tenant-1'))
        ->toBe('https://login.microsoftonline.com/tenant-1/oauth2/authorize');

    foreach ([CloudEnvironment::GCC, CloudEnvironment::GCC_HIGH, CloudEnvironment::DOD] as $env) {
        expect(CloudEnvironment::getAuthorizeEndpoint($env, 'tenant-1'))
            ->toBe('https://login.microsoftonline.us/tenant-1/oauth2/authorize');
    }
});

test('resolves token endpoint per environment', function () {
    expect(CloudEnvironment::getTokenEndpoint(CloudEnvironment::COMMERCIAL, 'tenant-1'))
        ->toBe('https://login.microsoftonline.com/tenant-1/oauth2/token');

    foreach ([CloudEnvironment::GCC, CloudEnvironment::GCC_HIGH, CloudEnvironment::DOD] as $env) {
        expect(CloudEnvironment::getTokenEndpoint($env, 'tenant-1'))
            ->toBe('https://login.microsoftonline.us/tenant-1/oauth2/token');
    }
});

test('resolves resource URL per environment', function () {
    expect(CloudEnvironment::getResourceUrl(CloudEnvironment::COMMERCIAL))
        ->toBe('https://analysis.windows.net/powerbi/api');
    expect(CloudEnvironment::getResourceUrl(CloudEnvironment::GCC))
        ->toBe('https://analysis.usgovcloudapi.net/powerbi/api');
    expect(CloudEnvironment::getResourceUrl(CloudEnvironment::GCC_HIGH))
        ->toBe('https://high.analysis.usgovcloudapi.net/powerbi/api');
    expect(CloudEnvironment::getResourceUrl(CloudEnvironment::DOD))
        ->toBe('https://mil.analysis.usgovcloudapi.net/powerbi/api');
});

test('isValid accepts supported environments and rejects others', function () {
    expect(CloudEnvironment::isValid(CloudEnvironment::COMMERCIAL))->toBeTrue();
    expect(CloudEnvironment::isValid(CloudEnvironment::GCC))->toBeTrue();
    expect(CloudEnvironment::isValid(CloudEnvironment::GCC_HIGH))->toBeTrue();
    expect(CloudEnvironment::isValid(CloudEnvironment::DOD))->toBeTrue();

    expect(CloudEnvironment::isValid('bogus'))->toBeFalse();
    expect(CloudEnvironment::isValid(''))->toBeFalse();
});

test('normalize falls back to commercial for null, empty, or unknown values', function () {
    expect(CloudEnvironment::normalize(null))->toBe(CloudEnvironment::COMMERCIAL);
    expect(CloudEnvironment::normalize(''))->toBe(CloudEnvironment::COMMERCIAL);
    expect(CloudEnvironment::normalize('mars'))->toBe(CloudEnvironment::COMMERCIAL);
    expect(CloudEnvironment::normalize(CloudEnvironment::GCC))->toBe(CloudEnvironment::GCC);
});

test('unknown environment routes to commercial endpoints', function () {
    expect(CloudEnvironment::getBaseUrl('mars'))
        ->toBe('https://api.powerbi.com/v1.0/myorg');
    expect(CloudEnvironment::getResourceUrl('mars'))
        ->toBe('https://analysis.windows.net/powerbi/api');
});
