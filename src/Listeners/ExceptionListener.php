<?php

namespace alhumsi\ErrorNotifier\Listeners;

use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use Throwable;

class ExceptionListener
{
    protected AnalyzerInterface $analyzer;

    public function __construct(AnalyzerInterface $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function handle(Throwable $e): void
    {
        $report = $this->analyzer->analyze($e);

        // For now, let's dump the analysis to confirm it works (temporary)
        dd($report);

        // Next task (Task 4) will use this $report to format and send a notification.
    }
}