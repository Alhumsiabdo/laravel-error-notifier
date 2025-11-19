<?php

namespace alhumsi\ErrorNotifier;

use alhumsi\ErrorNotifier\Console\FeatureLockCommand;
use alhumsi\ErrorNotifier\Http\Middleware\CheckFeatureLock;
use alhumsi\ErrorNotifier\Services\FeatureLocker;
use Illuminate\Contracts\Cache\Repository as Cache;
use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;
use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use alhumsi\ErrorNotifier\Listeners\ExceptionListener;
use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use Illuminate\Support\ServiceProvider;
use alhumsi\ErrorNotifier\Services\Maintainer;
use Illuminate\Contracts\Console\Kernel;
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
                $app->make(AnalyzerInterface::class),
                $app->make(MessageFormatterInterface::class),
                $app->make(NotifierInterface::class),
                $app->make(Throttler::class),
                $app->make(Maintainer::class),
                $app->make(FeatureLocker::class)
            );
        });
        $this->app->bind(NotifierInterface::class, Notifier::class);

        $this->app->singleton(Throttler::class, function ($app) {
            return new Throttler($app->make(Cache::class));
        });

        $this->app->singleton(Maintainer::class, function ($app) {
            return new Maintainer(
                $app->make(Kernel::class),
                $app->make(Cache::class)
            );
        });

        $this->app->singleton(FeatureLocker::class, function ($app) {
            return new FeatureLocker($app->make(Cache::class));
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                FeatureLockCommand::class,
            ]);
        }

        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('notifier.lock', CheckFeatureLock::class);
    }
}