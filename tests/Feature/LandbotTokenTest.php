<?php

namespace Fanfanfw\LandbotSecure\Tests\Feature;

use Fanfanfw\LandbotSecure\Tests\TestCase;

class LandbotTokenTest extends TestCase
{
    public function test_get_token_returns_200_with_token(): void
    {
        $response = $this->get('/__landbot/token');

        $response->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertSame(40, strlen((string) $response->json('token')));
    }

    public function test_get_token_stores_correct_data_in_session(): void
    {
        $this->get('/__landbot/token');

        $data = session('_landbot_pkg_token');

        $this->assertIsArray($data);
        $this->assertFalse($data['used']);
        $this->assertGreaterThan(now()->timestamp, $data['expires']);
        $this->assertIsString($data['hash']);
        $this->assertNotSame('', $data['hash']);
    }

    public function test_get_token_returns_404_when_disabled(): void
    {
        config(['landbot.enabled' => false]);

        $this->get('/__landbot/token')->assertNotFound();
    }

    public function test_get_token_returns_500_when_config_url_not_set(): void
    {
        config(['landbot.config_url' => null]);

        $this->get('/__landbot/token')->assertStatus(500);
    }
}
