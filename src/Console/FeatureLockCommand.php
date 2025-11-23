<?php

namespace alhumsi\ErrorNotifier\Console;

use Illuminate\Console\Command;
use alhumsi\ErrorNotifier\Services\FeatureLocker;

class FeatureLockCommand extends Command
{
    protected $signature = 'notifier:lock {feature} {--unlock : Unlock the feature instead of locking it}';
    protected $description = 'Locks or unlocks a feature controlled by the error notifier middleware.';

    protected FeatureLocker $locker;

    public function __construct(FeatureLocker $locker)
    {
        parent::__construct();
        $this->locker = $locker;
    }

    public function handle(): int
    {
        $feature = $this->argument('feature');
        $isUnlock = $this->option('unlock');

        if ($isUnlock) {
            $this->unlockFeature($feature);
        } else {
            $this->lockFeature($feature);
        }

        return 0;
    }

    protected function lockFeature(string $feature): void
    {
        if ($this->locker->isLocked($feature)) {
            $this->info("Feature '{$feature}' is already locked.");
            return;
        }
        $this->locker->lock($feature);
        $this->info("🔒 Feature '{$feature}' successfully locked.");
    }

    protected function unlockFeature(string $feature): void
    {
        if (!$this->locker->isLocked($feature)) {
            $this->warn("Feature '{$feature}' is already unlocked.");
            return;
        }
        $this->locker->unlock($feature);
        $this->info("✅ Feature '{$feature}' successfully unlocked.");
    }
}