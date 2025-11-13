<?php

namespace alhumsi\ErrorNotifier\Listeners;

use Throwable;
use Illuminate\Contracts\Debug\ExceptionHandler;

class ExceptionListener
{
    /**
     * Handle the exception reported by the Laravel application.
     * * @param \Throwable $e
     * @return void
     */
    public function handle(Throwable $e): void
    {
        // For Task 2, we just ensure we can receive the exception.
        // In the next task (Error Analyzer), we will process it here.

        // Log a simple message to confirm the listener is working
        // You can uncomment the line below for testing purposes:
        logger()->info('ErrorNotifier: Exception captured successfully.', ['exception' => get_class($e)]);
    }
}