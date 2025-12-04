<?php

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Connectors\PowerBIAzureUser;
use InterWorks\PowerBI\Enums\ConnectionAccountType;

test('can create PowerBIAzureUser connector', function () {
    $connector = new PowerBIAzureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://my-app.com/oauth/callback'
    );

    expect($connector)->toBeInstanceOf(PowerBIAzureUser::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::AzureUser);
});

test('resolves correct base URL', function () {
    $connector = new PowerBIAzureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://my-app.com/oauth/callback'
    );

    expect($connector->resolveBaseUrl())->toBe('https://api.powerbi.com/v1.0/myorg');
});

test('always uses AzureUser account type', function () {
    $connector = new PowerBIAzureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://my-app.com/oauth/callback'
    );

    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::AzureUser);
});

test('can generate authorization URL', function () {
    $connector = new PowerBIAzureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://my-app.com/oauth/callback'
    );

    $authUrl = $connector->getAuthorizationUrl();

    expect($authUrl)->toBeString();
    expect($authUrl)->toContain('https://login.microsoftonline.com/test-tenant/oauth2/authorize');
    expect($authUrl)->toContain('client_id=test-client-id');
    expect($authUrl)->toContain('redirect_uri=https');
    expect($authUrl)->toContain('response_type=code');
});

test('can retrieve state for CSRF protection', function () {
    $connector = new PowerBIAzureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://my-app.com/oauth/callback'
    );

    // Generate URL to initialize state
    $connector->getAuthorizationUrl();
    $state = $connector->getState();

    expect($state)->toBeString();
    expect($state)->not->toBeEmpty();
});

test('authorization URL includes default Power BI scopes', function () {
    $connector = new PowerBIAzureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://my-app.com/oauth/callback'
    );

    $authUrl = $connector->getAuthorizationUrl(scopes: [
        'https://analysis.windows.net/powerbi/api/.default',
        'offline_access',
    ]);

    // Scopes should be URL-encoded in the URL
    expect($authUrl)->toContain('scope=');
    expect($authUrl)->toContain('offline_access'); // For refresh token
});

test('caching is enabled by default', function () {
    $connector = new PowerBIAzureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://my-app.com/oauth/callback'
    );

    // Create a reflection class to access the protected cachingEnabled property
    $reflection = new ReflectionClass($connector);
    $isCachingEnabled = $reflection->getProperty('cachingEnabled');
    $isCachingEnabled->setAccessible(true);
    expect($isCachingEnabled->getValue($connector))->toBeTrue();
});

test('caching can be disabled via config', function () {
    Config::set('powerbi.cache.enabled', false);
    $connector = new PowerBIAzureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://my-app.com/oauth/callback'
    );

    // Create a reflection class to access the protected cachingEnabled property
    $reflection = new ReflectionClass($connector);
    $isCachingEnabled = $reflection->getProperty('cachingEnabled');
    $isCachingEnabled->setAccessible(true);
    expect($isCachingEnabled->getValue($connector))->toBeFalse();
});
