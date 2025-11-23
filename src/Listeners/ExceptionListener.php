<?php

namespace alhumsi\ErrorNotifier\Listeners;

use alhumsi\ErrorNotifier\Contracts\{
    AnalyzerInterface,
    MessageFormatterInterface,
    NotifierInterface
};
use alhumsi\ErrorNotifier\Services\{
    Maintainer,
    FeatureLocker
};
use alhumsi\ErrorNotifier\Throttler;
use Throwable;

class ExceptionListener
{
    protected AnalyzerInterface $analyzer;
    protected MessageFormatterInterface $formatter;
    protected NotifierInterface $notifier;
    protected Throttler $throttler;
    protected Maintainer $maintainer;
    protected FeatureLocker $locker;

    public function __construct(
        AnalyzerInterface $analyzer,
        MessageFormatterInterface $formatter,
        NotifierInterface $notifier,
        Throttler $throttler,
        Maintainer $maintainer,
        FeatureLocker $locker
    )
    {
        $this->analyzer = $analyzer;
        $this->formatter = $formatter;
        $this->notifier = $notifier;
        $this->throttler = $throttler;
        $this->maintainer = $maintainer;
        $this->locker = $locker;
    }

    public function handle(Throwable $e): void
    {
        $report = $this->analyzer->analyze($e);

        $level = $report['level'] ?? 'emergency';

        if (!$this->throttler->allowed($report)) {
            logger()->warning("ErrorNotifier: Notification for '{$level}' throttled.");
            return;
        }

        if ($level === 'emergency' && config('error-notifier.auto_actions.maintenance_enabled', true)) {
            $this->maintainer->down($level);
        }

        $featuresToLock = config("error-notifier.auto_actions.lock_features.{$level}", []);

        foreach ($featuresToLock as $feature) {
            if ($this->locker->lock($feature)) {
                logger()->warning("ErrorNotifier: Feature '{$feature}' was automatically locked due to {$level} error.");
            }
        }

        $channels = config("error-notifier.levels.{$level}", []);

        if (empty($channels)) {
            logger()->info("ErrorNotifier: No channels configured for level '{$level}'. Skipping notification.");
            return;
        }

        foreach ($channels as $channel) {
            $payload = $this->formatter->format($report, $channel);

            $success = $this->notifier->send($payload, $channel);

            logger()->info("ErrorNotifier: Sent notification via {$channel}. Success: {$success}");
        }
    }
}