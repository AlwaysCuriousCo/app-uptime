<?php

namespace AlwaysCurious\AppUptime\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AlwaysCurious\AppUptime\AppUptime
 */
class AppUptime extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AlwaysCurious\AppUptime\AppUptime::class;
    }
}
