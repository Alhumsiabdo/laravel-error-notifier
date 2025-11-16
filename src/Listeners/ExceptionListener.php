<?php

namespace alhumsi\ErrorNotifier\Listeners;

use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;
use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use Throwable;

class ExceptionListener
{
    protected AnalyzerInterface $analyzer;
    protected MessageFormatterInterface $formatter;
    protected NotifierInterface $notifier;

    public function __construct(AnalyzerInterface $analyzer, MessageFormatterInterface $formatter, NotifierInterface $notifier)
    {
        $this->analyzer = $analyzer;
        $this->formatter = $formatter;
        $this->notifier = $notifier;
    }

    public function handle(Throwable $e): void
    {
        $report = $this->analyzer->analyze($e);

        $channels = ['telegram', 'slack', 'discord'];

        foreach ($channels as $channel) {
            $payload = $this->formatter->format($report, $channel);

            $success = $this->notifier->send($payload, $channel);

            logger()->info("ErrorNotifier: Sent notification via {$channel}. Success: {$success}");
        }
    }
}