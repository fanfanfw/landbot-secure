<?php

namespace Fanfanfw\LandbotSecure\Tests\Unit;

use Fanfanfw\LandbotSecure\Facades\Landbot;
use Fanfanfw\LandbotSecure\Tests\TestCase;
use Fanfanfw\LandbotSecure\View\Components\LandbotWidget;

class LandbotConfigTest extends TestCase
{
    public function test_config_has_correct_defaults(): void
    {
        $this->assertSame('__landbot', config('landbot.route_prefix'));
        $this->assertSame(2, config('landbot.token_ttl'));
        $this->assertSame(10, config('landbot.rate_limit'));
        $this->assertFalse(config('landbot.logging'));
    }

    public function test_widget_disabled_when_enabled_is_false(): void
    {
        config(['landbot.enabled' => false]);

        $widget = new LandbotWidget();

        $this->assertFalse($widget->enabled);
        $this->assertSame('', $widget->tokenUrl);
        $this->assertSame('', $widget->configUrl);
    }

    public function test_facade_is_enabled_returns_correct_value(): void
    {
        config(['landbot.enabled' => true]);
        $this->assertTrue(Landbot::isEnabled());

        config(['landbot.enabled' => false]);
        $this->assertFalse(Landbot::isEnabled());
    }

    public function test_facade_get_route_prefix_returns_config_value(): void
    {
        $this->assertSame('__landbot', Landbot::getRoutePrefix());
    }

    public function test_widget_uses_relative_endpoints(): void
    {
        $widget = new LandbotWidget();

        $this->assertSame('/__landbot/token', $widget->tokenUrl);
        $this->assertSame('/__landbot/config', $widget->configUrl);
    }
}
