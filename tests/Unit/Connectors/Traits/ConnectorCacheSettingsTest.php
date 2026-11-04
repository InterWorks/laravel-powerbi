<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\FileStore;
use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;

test('uses default cache expiry of 3600 seconds', function () {
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    expect($connector->cacheExpiryInSeconds())->toBe(3600);
});

test('uses custom cache expiry from config', function () {
    Config::set('powerbi.cache.expiry_seconds', 7200);
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    expect($connector->cacheExpiryInSeconds())->toBe(7200);
});

test('uses default Laravel cache store', function () {
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $driver = $connector->resolveCacheDriver();
    expect($driver)->toBeInstanceOf(LaravelCacheDriver::class);
});

test('supports Laravel file cache store', function () {
    // Set the default cache store to 'file'
    Config::set('cache.default', 'file');
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $driver = $connector->resolveCacheDriver();
    expect($driver)->toBeInstanceOf(LaravelCacheDriver::class);

    // Create a reflection class to access the protected store property
    $reflection = new ReflectionClass($driver);
    $storeProperty = $reflection->getProperty('store');
    $storeProperty->setAccessible(true);
    $store = $storeProperty->getValue($driver);
    expect($store->getStore())->toBeInstanceOf(FileStore::class);
});

test('supports Laravel array cache store', function () {
    // Set the default cache store to 'array'
    Config::set('cache.default', 'array');
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $driver = $connector->resolveCacheDriver();
    expect($driver)->toBeInstanceOf(LaravelCacheDriver::class);

    // Create a reflection class to access the protected store property
    $reflection = new ReflectionClass($driver);
    $storeProperty = $reflection->getProperty('store');
    $storeProperty->setAccessible(true);
    $store = $storeProperty->getValue($driver);
    expect($store->getStore())->toBeInstanceOf(ArrayStore::class);
});

test('throws exception when cache expiry is null', function () {
    Config::set('powerbi.cache.expiry_seconds', null);
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $connector->cacheExpiryInSeconds();
})->throws(InvalidArgumentException::class, 'cannot be null');

test('throws exception when cache expiry is 0', function () {
    Config::set('powerbi.cache.expiry_seconds', 0);
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $connector->cacheExpiryInSeconds();
})->throws(InvalidArgumentException::class, 'must be an integer >= 1');

test('throws exception when cache expiry is negative', function () {
    Config::set('powerbi.cache.expiry_seconds', -100);
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $connector->cacheExpiryInSeconds();
})->throws(InvalidArgumentException::class, 'must be an integer >= 1');

test('accepts numeric string cache expiry from env()', function () {
    Config::set('powerbi.cache.expiry_seconds', '3600');
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    expect($connector->cacheExpiryInSeconds())->toBe(3600);
});

test('throws exception when cache expiry is a non-numeric string', function () {
    Config::set('powerbi.cache.expiry_seconds', 'one-hour');
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $connector->cacheExpiryInSeconds();
})->throws(InvalidArgumentException::class, 'must be an integer >= 1');

test('accepts the minimum cache expiry of 1', function (int|string $expiry) {
    Config::set('powerbi.cache.expiry_seconds', $expiry);
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    expect($connector->cacheExpiryInSeconds())->toBe(1);
})->with([1, '1']);

test('null cache expiry error explains how to fix it', function () {
    Config::set('powerbi.cache.expiry_seconds', null);
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $connector->cacheExpiryInSeconds();
})->throws(
    InvalidArgumentException::class,
    'Power BI cache expiry seconds cannot be null. Set a value >= 1 in config/powerbi.php '
        .'or disable caching by setting cache.enabled = false.'
);

test('invalid cache expiry error reports the value and its type', function (mixed $expiry, string $received) {
    Config::set('powerbi.cache.expiry_seconds', $expiry);
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');

    expect(fn () => $connector->cacheExpiryInSeconds())->toThrow(
        InvalidArgumentException::class,
        "Power BI cache expiry seconds must be an integer >= 1. Received: {$received}. "
            .'Set cache.enabled = false in config/powerbi.php to disable caching.'
    );
})->with([
    'non-numeric string' => ['soon', 'soon (string)'],
    'float' => [1.5, '1.5 (float)'],
    'array' => [[60], 'array (array)'],
]);
