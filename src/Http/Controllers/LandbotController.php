<?php

namespace Fanfanfw\LandbotSecure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LandbotController extends Controller
{
    public function getToken(Request $request): JsonResponse
    {
        abort_if(! config('landbot.enabled', true), 404);
        abort_if(! config('landbot.config_url'), 500, 'LANDBOT_CONFIG_URL is not configured.');

        $token = Str::random(40);
        $ttl = max(1, (int) config('landbot.token_ttl', 2));

        $request->session()->put('_landbot_pkg_token', [
            'hash' => hash('sha256', $token),
            'expires' => now()->addMinutes($ttl)->timestamp,
            'used' => false,
        ]);

        $this->log('info', '[Landbot] Token issued. IP: ' . $request->ip());

        return response()->json(['token' => $token], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function getConfig(Request $request): Response
    {
        abort_if(! config('landbot.enabled', true), 404);

        $data = $request->session()->get('_landbot_pkg_token');
        $token = $request->input('token');
        $configUrl = config('landbot.config_url');

        if (! is_array($data) || ! is_string($token) || $token === '') {
            $this->log('warning', '[Landbot] 403 No token. IP: ' . $request->ip());
            abort(403, 'No token.');
        }

        if (! isset($data['hash'], $data['expires'], $data['used'])) {
            $this->log('warning', '[Landbot] 403 Invalid token state. IP: ' . $request->ip());
            abort(403, 'No token.');
        }

        if ((bool) $data['used'] === true) {
            $this->log('warning', '[Landbot] 403 Token already used. IP: ' . $request->ip());
            abort(403, 'Token already used.');
        }

        if (now()->timestamp > (int) $data['expires']) {
            $this->log('warning', '[Landbot] 403 Token expired. IP: ' . $request->ip());
            abort(403, 'Token expired.');
        }

        if (! is_string($data['hash']) || ! hash_equals($data['hash'], hash('sha256', $token))) {
            $this->log('warning', '[Landbot] 403 Token mismatch. IP: ' . $request->ip());
            abort(403, 'Token mismatch.');
        }

        abort_if(! $configUrl, 500, 'LANDBOT_CONFIG_URL is not configured.');

        $request->session()->put('_landbot_pkg_token.used', true);

        $response = Http::acceptJson()
            ->timeout(10)
            ->get($configUrl);

        if (! $response->ok()) {
            $this->log('error', '[Landbot] 502 Proxy failed.');
            abort(502, 'Failed to reach Landbot.');
        }

        $this->log('info', '[Landbot] Config proxied successfully. IP: ' . $request->ip());

        return response($response->body(), 200)
            ->header('Content-Type', $response->header('Content-Type', 'application/json'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function log(string $level, string $message): void
    {
        if (! config('landbot.logging', true)) {
            return;
        }

        $channel = config('landbot.log_channel');

        if ($channel) {
            Log::channel($channel)->{$level}($message);

            return;
        }

        Log::{$level}($message);
    }
}
