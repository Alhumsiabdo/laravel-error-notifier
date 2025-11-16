<?php

namespace alhumsi\ErrorNotifier\Contracts;

use Throwable;
interface AnalyzerInterface
{
    /**
     * Analyzes an exception and returns structured data for notification.
     *
     * @param Throwable $exception The raw exception caught.
     * @return array ['level', 'type', 'summary', 'context']
     */
    public function analyze(Throwable $exception): array;
}