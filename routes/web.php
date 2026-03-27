<?php

use Fanfanfw\LandbotSecure\Http\Controllers\LandbotController;
use Illuminate\Support\Facades\Route;

$prefix = config('landbot.route_prefix', '__landbot');
$extraMiddleware = (array) config('landbot.middleware', []);
$rateLimit = (int) config('landbot.rate_limit', 10);

$middleware = array_merge(
    ['web'],
    ["throttle:{$rateLimit},1"],
    $extraMiddleware
);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('landbot.')
    ->group(function (): void {
        Route::get('/token', [LandbotController::class, 'getToken'])
            ->name('token');

        Route::post('/config', [LandbotController::class, 'getConfig'])
            ->name('config');
    });
