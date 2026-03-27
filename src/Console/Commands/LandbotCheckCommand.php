<?php

namespace Fanfanfw\LandbotSecure\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class LandbotCheckCommand extends Command
{
    protected $signature = 'landbot:check';

    protected $description = 'Validate Landbot Secure package configuration';

    public function handle(): int
    {
        $this->line('');
        $this->line('Landbot Secure - Configuration Check');
        $this->line('====================================');

        $passed = true;
        $configUrl = config('landbot.config_url');

        if (! $configUrl) {
            $this->error('x LANDBOT_CONFIG_URL   : not set. Add it to your .env file.');
            $passed = false;
        } else {
            $this->info('OK LANDBOT_CONFIG_URL  : set');
        }

        if ($configUrl && ! str_starts_with($configUrl, 'https://')) {
            $this->error('x URL format           : must start with https://');
            $passed = false;
        } elseif ($configUrl) {
            $this->info('OK URL format          : valid');
        }

        if ($configUrl && str_starts_with($configUrl, 'https://')) {
            try {
                $response = Http::acceptJson()
                    ->timeout(5)
                    ->get($configUrl);

                if (! $response->ok()) {
                    $this->error('x URL reachable        : HTTP ' . $response->status());
                    $passed = false;
                } else {
                    $this->info('OK URL reachable       : HTTP ' . $response->status());

                    json_decode($response->body(), true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->error('x Response is JSON     : invalid');
                        $passed = false;
                    } else {
                        $this->info('OK Response is JSON    : valid');
                    }
                }
            } catch (Throwable $exception) {
                $this->error('x URL reachable        : connection failed. ' . $exception->getMessage());
                $passed = false;
            }
        }

        $enabled = (bool) config('landbot.enabled', true);
        if ($enabled) {
            $this->info('OK Widget enabled      : true');
        } else {
            $this->warn('! Widget enabled       : false');
        }

        $this->line('');
        $this->line('Active configuration:');
        $this->line('  Route prefix   : ' . config('landbot.route_prefix', '__landbot'));
        $this->line('  Token TTL      : ' . config('landbot.token_ttl', 2) . ' minutes');
        $this->line('  Rate limit     : ' . config('landbot.rate_limit', 10) . ' req/min');
        $this->line('  Logging        : ' . (config('landbot.logging', true) ? 'enabled' : 'disabled'));
        $this->line('  Log channel    : ' . (config('landbot.log_channel') ?: 'default'));
        $this->line('');

        if (! $passed) {
            $this->error('Some checks failed. Fix the issues above before deploying.');

            return self::FAILURE;
        }

        $this->info('All checks passed. Your Landbot widget is ready.');

        return self::SUCCESS;
    }
}
