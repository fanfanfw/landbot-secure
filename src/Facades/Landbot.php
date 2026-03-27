<?php

namespace Fanfanfw\LandbotSecure\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static string getRoutePrefix()
 */
class Landbot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'landbot-secure';
    }
}
