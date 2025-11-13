<?php

namespace InterWorks\PowerBI\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \InterWorks\PowerBI\PowerBI
 */
class PowerBI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \InterWorks\PowerBI\PowerBI::class;
    }
}
