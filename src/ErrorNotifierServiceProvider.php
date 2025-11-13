<?php

namespace Alhumsibsaido\ErrorNotifier;

use Illuminate\Support\ServiceProvider;

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

        // Register event/listeners here
    }
}
