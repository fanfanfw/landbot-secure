# fanfanfw/landbot-secure

`fanfanfw/landbot-secure` is a Laravel package that hardens Landbot widget embedding by hiding the original `configUrl` behind a session-bound proxy flow.

It does not modify the bot logic inside Landbot. It only changes how the browser obtains the config needed to bootstrap the widget.

## What It Solves

Landbot's default embed snippet exposes a public `configUrl` in browser source. If someone copies that URL, they can attempt to reuse the same bot elsewhere.

This package reduces that risk by:

- Keeping the original `LANDBOT_CONFIG_URL` server-side only
- Issuing a short-lived one-time token via a Laravel `web` session
- Requiring CSRF protection for config bootstrap
- Proxying the Landbot config through Laravel before the SDK is initialized

## Security Boundary

This package is a hardening layer, not DRM.

- It hides the original `configUrl` from HTML and bootstrap JavaScript.
- It does not make the proxied config payload impossible to inspect in a legitimate browser session.
- It does not protect against XSS on the host application.
- It does not replace a native vendor-side domain restriction feature.

## Requirements

- PHP `^8.1`
- Laravel `^10.0|^11.0|^12.0|^13.0`
- A standard Laravel layout with `<meta name="csrf-token" content="{{ csrf_token() }}">`

Host PHP minimum still follows the Laravel version you install:

- Laravel 10: PHP 8.1+
- Laravel 11-12: PHP 8.2+
- Laravel 13: PHP 8.3+

## Installation

```bash
composer require fanfanfw/landbot-secure
```

Add your Landbot config URL to `.env`:

```env
LANDBOT_CONFIG_URL=https://storage.googleapis.com/landbot.online/v3/H-XXXXXXX-XXXXXXXXXXXXXXXXX/index.json
```

Ensure your layout has a CSRF meta tag:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

Render the widget:

```blade
<x-landbot::widget />
```

Validate the setup:

```bash
php artisan landbot:check
```

## Configuration

You may publish the config file:

```bash
php artisan vendor:publish --tag=landbot-config
```

Available environment variables:

| Key | Required | Default | Description |
|---|---|---|---|
| `LANDBOT_CONFIG_URL` | Yes | — | Original Landbot config URL |
| `LANDBOT_ENABLED` | No | `true` | Enable or disable the widget |
| `LANDBOT_ROUTE_PREFIX` | No | `__landbot` | Internal route prefix |
| `LANDBOT_TOKEN_TTL` | No | `2` | Token lifetime in minutes |
| `LANDBOT_RATE_LIMIT` | No | `10` | `/token` requests per minute per IP |
| `LANDBOT_LOGGING` | No | `true` | Enable package logging |
| `LANDBOT_LOG_CHANNEL` | No | `null` | Optional Laravel log channel |

## Usage

Basic usage in any Blade view:

```blade
<x-landbot::widget />
```

Optional facade usage:

```php
use Landbot;

Landbot::isEnabled();
Landbot::getRoutePrefix();
```

## Publishable Assets

Publish config:

```bash
php artisan vendor:publish --tag=landbot-config
```

Publish views:

```bash
php artisan vendor:publish --tag=landbot-views
```

## How It Works

1. The browser lazily requests `GET /__landbot/token`.
2. Laravel stores a hashed one-time token in the session and returns the raw token.
3. The browser sends the token to `POST /__landbot/config` with CSRF protection.
4. Laravel validates the session-bound token, fetches the upstream Landbot config, and returns proxied JSON.
5. The package bootstraps Landbot using a Blob URL so the original upstream `configUrl` is not embedded directly in the page.

## Testing

```bash
./vendor/bin/phpunit --testdox
```

## License

MIT
