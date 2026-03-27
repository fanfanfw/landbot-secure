<?php

namespace Fanfanfw\LandbotSecure\Tests\Feature;

use Fanfanfw\LandbotSecure\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class LandbotConfigProxyTest extends TestCase
{
    public function test_post_config_returns_proxied_json_with_valid_token(): void
    {
        Http::fake([
            'storage.googleapis.com/*' => Http::response(['messages' => []], 200),
        ]);

        $issued = $this->issueToken();

        $response = $this->withSession($issued['session'])
            ->postJson('/__landbot/config', ['token' => $issued['token']], [
                'X-CSRF-TOKEN' => $issued['csrf'],
            ]);

        $response->assertOk()
            ->assertJson(['messages' => []]);

        $this->assertStringNotContainsString('storage.googleapis.com', $response->getContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_post_config_returns_403_without_token(): void
    {
        $csrf = 'csrf-token';

        $response = $this->withSession(['_token' => $csrf])
            ->postJson('/__landbot/config', [], [
                'X-CSRF-TOKEN' => $csrf,
            ]);

        $response->assertStatus(403);
    }

    public function test_post_config_returns_403_with_wrong_token(): void
    {
        $issued = $this->issueToken();

        $response = $this->withSession($issued['session'])
            ->postJson('/__landbot/config', ['token' => 'wrong-token-value'], [
                'X-CSRF-TOKEN' => $issued['csrf'],
            ]);

        $response->assertStatus(403);
    }

    public function test_post_config_returns_403_with_expired_token(): void
    {
        $issued = $this->issueToken();
        $session = $issued['session'];
        $session['_landbot_pkg_token']['expires'] = now()->subMinutes(5)->timestamp;

        $response = $this->withSession($session)
            ->postJson('/__landbot/config', ['token' => $issued['token']], [
                'X-CSRF-TOKEN' => $issued['csrf'],
            ]);

        $response->assertStatus(403);
    }

    public function test_post_config_returns_403_when_token_used_twice(): void
    {
        Http::fake([
            'storage.googleapis.com/*' => Http::response(['messages' => []], 200),
        ]);

        $issued = $this->issueToken();

        $this->withSession($issued['session'])
            ->postJson('/__landbot/config', ['token' => $issued['token']], [
                'X-CSRF-TOKEN' => $issued['csrf'],
            ])
            ->assertOk();

        $usedSession = session()->all();

        $this->withSession($usedSession)
            ->postJson('/__landbot/config', ['token' => $issued['token']], [
                'X-CSRF-TOKEN' => $issued['csrf'],
            ])
            ->assertStatus(403);
    }

    public function test_post_config_route_uses_web_middleware_group(): void
    {
        $route = app('router')->getRoutes()->getByName('landbot.config');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->middleware());
        $this->assertContains('throttle:10,1', $route->middleware());
    }

    public function test_post_config_returns_502_when_landbot_unreachable(): void
    {
        Http::fake([
            'storage.googleapis.com/*' => Http::response(null, 500),
        ]);

        $issued = $this->issueToken();

        $this->withSession($issued['session'])
            ->postJson('/__landbot/config', ['token' => $issued['token']], [
                'X-CSRF-TOKEN' => $issued['csrf'],
            ])
            ->assertStatus(502);
    }

    public function test_post_config_returns_404_when_disabled(): void
    {
        config(['landbot.enabled' => false]);

        $csrf = 'csrf-token';

        $this->withSession(['_token' => $csrf])
            ->postJson('/__landbot/config', [], [
                'X-CSRF-TOKEN' => $csrf,
            ])
            ->assertStatus(404);
    }

    /**
     * @return array{token: string, csrf: string, session: array<string, mixed>}
     */
    private function issueToken(): array
    {
        $csrf = 'csrf-token';

        $response = $this->withSession(['_token' => $csrf])
            ->get('/__landbot/token');

        $response->assertOk();

        return [
            'token' => (string) $response->json('token'),
            'csrf' => $csrf,
            'session' => session()->all(),
        ];
    }
}
