<?php

// Architecture tests require pest-plugin-arch v2+, which requires Pest v2+
// These tests will be skipped when using Pest v1.x (Laravel 9)
if (function_exists('arch')) {
    arch('it will not use debugging functions')
        ->expect(['dd', 'dump', 'ray'])
        ->each->not->toBeUsed();
}
