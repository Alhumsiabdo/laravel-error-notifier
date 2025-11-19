<?php

namespace alhumsi\ErrorNotifier\Services;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Output\BufferedOutput;

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
     */
    public function down(string $level): bool
    {
        $cooldownKey = 'error-notifier:action:maintenance';
        $cooldownMinutes = config('error-notifier.auto_actions.maintenance_cooldown_minutes', 15);

        if ($this->cache->has($cooldownKey)) {
            Log::warning("ErrorNotifier: Maintenance mode skipped due to active cooldown.");
            return false;
        }

        $output = new BufferedOutput();
        $exitCode = $this->artisan->call('down', [
            '--secret' => config('error-notifier.auto_actions.maintenance_secret'),
            '--message' => "Application disabled due to an automatic system error ({$level})."
        ], $output);

        if ($exitCode === 0) {
            $this->cache->put($cooldownKey, true, Carbon::now()->addMinutes($cooldownMinutes));
            Log::critical("ErrorNotifier: Application successfully placed into maintenance mode due to {$level} error.");
            return true;
        } else {
            Log::error("ErrorNotifier: 'artisan down' command failed (Exit Code: {$exitCode}). Output: " . $output->fetch());
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