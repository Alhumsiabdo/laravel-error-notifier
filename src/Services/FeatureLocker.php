<?php

namespace alhumsi\ErrorNotifier\Services;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Config;

class FeatureLocker
{
    protected Cache $cache;
    protected string $keyPrefix;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->keyPrefix = Config::get('error-notifier.auto_actions.lock_key_prefix', 'error-notifier:lock:');
    }

    /**
     * Locks a specific feature by setting a flag in the cache.
     * @param string $featureName The name of the feature to lock (e.g., 'checkout').
     * @return bool True if successfully locked.
     */
    public function lock(string $featureName): bool
    {
        $key = $this->keyPrefix . $featureName;
        return $this->cache->forever($key, true);
    }

    /**
     * Unlocks a feature by removing the flag from the cache.
     * @param string $featureName
     * @return bool True if successfully unlocked.
     */
    public function unlock(string $featureName): bool
    {
        $key = $this->keyPrefix . $featureName;
        return $this->cache->forget($key);
    }

    /**
     * Checks if a specific feature is currently locked.
     * @param string $featureName
     * @return bool True if the feature is locked.
     */
    public function isLocked(string $featureName): bool
    {
        $key = $this->keyPrefix . $featureName;
        return $this->cache->has($key);
    }
}