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

test('throws exception when cache expiry is string', function () {
    Config::set('powerbi.cache.expiry_seconds', '3600');
    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $connector->cacheExpiryInSeconds();
})->throws(InvalidArgumentException::class, 'must be an integer >= 1');
