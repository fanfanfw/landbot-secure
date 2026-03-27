<?php

namespace Fanfanfw\LandbotSecure;

use Fanfanfw\LandbotSecure\Console\Commands\LandbotCheckCommand;
use Fanfanfw\LandbotSecure\View\Components\LandbotWidget;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class LandbotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/landbot.php', 'landbot');

        $this->app->bind('landbot-secure', function () {
            return new class {
                public function isEnabled(): bool
                {
                    return (bool) config('landbot.enabled', true);
                }

                public function getRoutePrefix(): string
                {
                    return (string) config('landbot.route_prefix', '__landbot');
                }
            };
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'landbot');
        Blade::component('landbot::widget', LandbotWidget::class);

        if (config('landbot.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/landbot.php' => config_path('landbot.php'),
        ], 'landbot-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/landbot'),
        ], 'landbot-views');

        $this->commands([
            LandbotCheckCommand::class,
        ]);
    }
}
