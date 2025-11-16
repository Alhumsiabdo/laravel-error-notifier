<?php

namespace alhumsi\ErrorNotifier\Listeners;

use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;
use Throwable;

class ExceptionListener
{
    protected AnalyzerInterface $analyzer;
    protected MessageFormatterInterface $formatter;

    public function __construct(AnalyzerInterface $analyzer, MessageFormatterInterface $formatter)
    {
        $this->analyzer = $analyzer;
        $this->formatter = $formatter;
    }

    public function handle(Throwable $e): void
    {
        // This line now works because $formatter is declared above.
        $report = $this->analyzer->analyze($e);
        $payload = $this->formatter->format($report, 'slack');

        dd($payload);
    }
}