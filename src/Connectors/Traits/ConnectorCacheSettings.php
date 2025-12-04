<?php

namespace InterWorks\PowerBI\Connectors\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;

/**
 * Connector cache settings for Power BI connectors.
 *
 * Provides cache configuration methods required by Saloon's Cacheable interface.
 * Reads configuration from config/powerbi.php cache section.
 */
trait ConnectorCacheSettings
{
    /**
     * Resolve the cache driver to use for storing responses.
     *
     * Uses Laravel's default cache store as configured in config/cache.php.
     */
    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(Cache::store());
    }

    /**
     * Get the cache expiry time in seconds.
     *
     * Validates that expiry_seconds is an integer >= 1.
     * Throws InvalidArgumentException if validation fails.
     */
    public function cacheExpiryInSeconds(): int
    {
        $expiry = Config::get('powerbi.cache.expiry_seconds');

        // Validate not null
        if ($expiry === null) {
            throw new InvalidArgumentException(
                'Power BI cache expiry seconds cannot be null. '.
                'Set a value >= 1 in config/powerbi.php or disable caching by setting '.
                'cache.enabled = false.'
            );
        }

        // Validate is integer and >= 1
        if (! is_int($expiry) || $expiry < 1) {
            $type = get_debug_type($expiry);
            $value = is_scalar($expiry) ? (string) $expiry : $type;

            throw new InvalidArgumentException(
                "Power BI cache expiry seconds must be an integer >= 1. Received: {$value} ({$type}). ".
                'Set cache.enabled = false in config/powerbi.php to disable caching.'
            );
        }

        return $expiry;
    }
}
