<?php

namespace alhumsi\ErrorNotifier\Listeners;

use Illuminate\Log\Events\MessageLogged;
use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;
use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use alhumsi\ErrorNotifier\Throttler;

class LogListener
{
    protected MessageFormatterInterface $formatter;
    protected NotifierInterface $notifier;
    protected Throttler $throttler;

    public function __construct(
        MessageFormatterInterface $formatter,
        NotifierInterface $notifier,
        Throttler $throttler
    ) {
        $this->formatter = $formatter;
        $this->notifier = $notifier;
        $this->throttler = $throttler;
    }

    public function handle(MessageLogged $event): void
    {
        // 1. Check if this log level is configured for notifications
        $level = $event->level;
        $channels = config("error-notifier.levels.{$level}", []);

        if (empty($channels)) {
            return;
        }

        // 2. Prevent duplicate notifications for exceptions (handled by ExceptionListener)
        if (isset($event->context['exception']) && $event->context['exception'] instanceof \Throwable) {
            return;
        }

        // 3. Construct the report
        $report = [
            'level' => $level,
            'type' => 'log_message',
            'summary' => $event->message,
            'context' => $event->context,
            'suggestion' => 'Check application logs for more details.'
        ];

        // 4. Check throttling
        if (!$this->throttler->allowed($report)) {
            return;
        }

        // 5. Send notifications
        foreach ($channels as $channel) {
            $payload = $this->formatter->format($report, $channel);
            $this->notifier->send($payload, $channel);
        }
    }
}
