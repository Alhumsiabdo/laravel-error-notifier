<?php

namespace alhumsi\ErrorNotifier;

use alhumsi\ErrorNotifier\Listeners\ExceptionListener;
use Illuminate\Support\ServiceProvider;
use Throwable;

class ErrorNotifierServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/error-notifier.php', 'error-notifier');
        // Bind services
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/error-notifier.php' => config_path('error-notifier.php')
        ], 'error-notifier-config');

        // This is the implementation for Task 2: Hooking into the handler
        $this->app->make(ExceptionHandler::class)->reportable(function (Throwable $e) {

            // 1. Check configuration to see if we should handle this exception (e.g., skip 404s).
            // 2. Instantiate and call your listener/analyzer here.

            // For now, call the Listener directly to satisfy the task's acceptance criteria:
            if (class_exists(ExceptionListener::class)) {
                (new ExceptionListener())->handle($e);
            }
        });
    }
}
