<?php

namespace alhumsi\ErrorNotifier;

use alhumsi\ErrorNotifier\Listeners\ExceptionListener;
use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface; // <-- NEW: Import the Interface
use alhumsi\ErrorNotifier\Analyzer; // <-- NEW: Import the concrete Analyzer class
use Illuminate\Support\ServiceProvider;
use Throwable;

class ErrorNotifierServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/error-notifier.php', 'error-notifier');

        // 1. BIND THE ANALYZER INTERFACE TO THE CONCRETE ANALYZER CLASS (Fixes the instantiable error)
        $this->app->bind(AnalyzerInterface::class, Analyzer::class);

        // 2. BIND THE LISTENER (Ensures the constructor dependencies are handled)
        // We use singleton because we only need one listener instance.
        $this->app->singleton(ExceptionListener::class, function ($app) {
            // Laravel resolves AnalyzerInterface when the Listener is created
            return new ExceptionListener(
                $app->make(AnalyzerInterface::class)
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/error-notifier.php' => config_path('error-notifier.php')
        ], 'error-notifier-config');

        $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)->reportable(function (\Throwable $e) {

            // The listener is now resolved from the container, getting its dependencies automatically.
            $listener = $this->app->make(ExceptionListener::class);
            $listener->handle($e);
        });
    }
}