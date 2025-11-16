<?php

namespace alhumsi\ErrorNotifier;

use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;
use alhumsi\ErrorNotifier\Listeners\ExceptionListener;
use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use Illuminate\Support\ServiceProvider;
use Throwable;

class ErrorNotifierServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/error-notifier.php', 'error-notifier');

        // 1. Bind the concrete Analyzer class
        $this->app->bind(AnalyzerInterface::class, Analyzer::class);

        // 2. Bind the concrete Formatter class
        $this->app->bind(MessageFormatterInterface::class, MessageFormatter::class);

        // 3. FIX: When creating the Listener, pass both required dependencies
        $this->app->singleton(ExceptionListener::class, function ($app) {
            return new ExceptionListener(
                $app->make(AnalyzerInterface::class),      // Argument 1
                $app->make(MessageFormatterInterface::class) // Argument 2 (The missing one!)
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/error-notifier.php' => config_path('error-notifier.php')
        ], 'error-notifier-config');

        $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)->reportable(function (\Throwable $e) {

            $listener = $this->app->make(ExceptionListener::class);
            $listener->handle($e);
        });
    }
}