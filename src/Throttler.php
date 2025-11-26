<?php

namespace alhumsi\ErrorNotifier;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Carbon;

class Throttler
{
    protected Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function allowed(array $report): bool
    {
        if (!config('error-notifier.throttling.enabled')) {
            return true;
        }

        $cooldown = config('error-notifier.throttling.default_cooldown_minutes', 60);
        $key = $this->generateKey($report);

        if ($this->cache->has($key)) {
            return false;
        }

        $this->cache->put($key, true, Carbon::now()->addMinutes($cooldown));
        return true;
    }

    protected function generateKey(array $report): string
    {
        if (isset($report['fingerprint'])) {
            return "error-notifier:throttle:{$report['fingerprint']}";
        }

        // Fallback for backward compatibility or manual payloads
        $type = $report['type'] ?? 'unknown';
        $level = $report['level'] ?? 'emergency';
        return "error-notifier:throttle:{$level}:{$type}";
    }
}