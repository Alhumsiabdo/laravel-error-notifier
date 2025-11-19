<?php

namespace alhumsi\ErrorNotifier\Listeners;

use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;
use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use alhumsi\ErrorNotifier\Throttler;
use Throwable;

class ExceptionListener
{
    protected AnalyzerInterface $analyzer;
    protected MessageFormatterInterface $formatter;
    protected NotifierInterface $notifier;
    protected Throttler $throttler;

    public function __construct(AnalyzerInterface $analyzer, MessageFormatterInterface $formatter, NotifierInterface $notifier, Throttler $throttler)
    {
        $this->analyzer = $analyzer;
        $this->formatter = $formatter;
        $this->notifier = $notifier;
        $this->throttler = $throttler;
    }

    public function handle(Throwable $e): void
    {
        $report = $this->analyzer->analyze($e);

        $level = $report['level'] ?? 'emergency';

        if (!$this->throttler->allowed($report)) {
            logger()->warning("ErrorNotifier: Notification for '{$level}' throttled.");
            return;
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