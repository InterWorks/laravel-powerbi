<?php

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
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
