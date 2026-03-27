<?php

namespace Fanfanfw\LandbotSecure\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class LandbotWidget extends Component
{
    public bool $enabled;

    public string $tokenUrl;

    public string $configUrl;

    public function __construct()
    {
        $this->enabled = (bool) config('landbot.enabled', true);
        $this->tokenUrl = $this->enabled && Route::has('landbot.token')
            ? route('landbot.token')
            : '';
        $this->configUrl = $this->enabled && Route::has('landbot.config')
            ? route('landbot.config')
            : '';
    }

    public function render(): View
    {
        return view('landbot::components.widget');
    }
}
