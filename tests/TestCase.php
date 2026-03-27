<?php

namespace Fanfanfw\LandbotSecure\Tests;

use Fanfanfw\LandbotSecure\LandbotServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LandbotServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Landbot' => \Fanfanfw\LandbotSecure\Facades\Landbot::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', '12345678901234567890123456789012');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('landbot.config_url', 'https://storage.googleapis.com/landbot.online/v3/H-TEST/index.json');
        $app['config']->set('landbot.enabled', true);
        $app['config']->set('landbot.token_ttl', 2);
        $app['config']->set('landbot.rate_limit', 10);
        $app['config']->set('landbot.logging', false);
        $app['config']->set('landbot.route_prefix', '__landbot');
        $app['config']->set('landbot.middleware', []);
    }
}
