<?php

namespace alhumsi\ErrorNotifier\Services;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;

class Maintainer
{
    protected Kernel $artisan;
    protected Cache $cache;

    public function __construct(Kernel $artisan, Cache $cache)
    {
        $this->artisan = $artisan;
        $this->cache = $cache;
    }

    /**
     * Puts the application into maintenance mode if the cooldown allows it.
     * @param string $level The severity level (e.g., 'emergency').
     * @return bool True if maintenance mode was engaged, false otherwise.
     */
    public function down(string $level): bool
    {
        // Define a key for cooldown tracking
        $cooldownKey = 'error-notifier:action:maintenance';
        $cooldownMinutes = config('error-notifier.auto_actions.maintenance_cooldown_minutes', 15);

        // 1. Check Cooldown
        if ($this->cache->has($cooldownKey)) {
            Log::warning("ErrorNotifier: Maintenance mode skipped due to active cooldown.");
            return false;
        }

        // 2. Execute 'artisan down' command
        $exitCode = $this->artisan->call('down', [
            '--secret' => config('error-notifier.auto_actions.maintenance_secret'),
//            '--message' => "Application disabled due to an automatic system error ({$level})."
        ]);

        if ($exitCode === 0) {
            // 3. Set Cooldown after successful execution
            $this->cache->put($cooldownKey, true, now()->addMinutes($cooldownMinutes));
            Log::critical("ErrorNotifier: Application successfully placed into maintenance mode due to {$level} error.");
            return true;
        }

        return false;
    }

    /**
     * Provides an artisan command method to manually lift maintenance mode.
     */
    public function up(): int
    {
        return $this->artisan->call('up');
    }
}