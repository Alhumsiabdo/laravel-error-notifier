<?php

return [
    /*
     * -------------------------------------------------------------------------
     * Channels & Configuration
     * -------------------------------------------------------------------------
     */
    'channels' => [
        'slack' => [
            'webhook_url' => env('ERROR_NOTIFIER_SLACK_WEBHOOK'),
        ],
        'telegram' => [
            'bot_token' => env('ERROR_NOTIFIER_TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('ERROR_NOTIFIER_TELEGRAM_CHAT_ID'),
            'bot_url' => 'https://api.telegram.org/bot'
        ],
        'discord' => [
            'webhook_url' => env('ERROR_NOTIFIER_DISCORD_WEBHOOK'),
        ],
    ],

    /*
     * -------------------------------------------------------------------------
     * Default Notification Channels & Severities
     * -------------------------------------------------------------------------
     */
    'levels' => [
        'emergency' => ['slack', 'telegram', 'discord'],
        'critical' => ['slack'],
        'error' => ['slack'],
        'warning' => [],
        'notice' => [],
    ],

    /*
     * -------------------------------------------------------------------------
     * Analyzer Mapping
     * -------------------------------------------------------------------------
     */
    'analyzers' => [
        \RuntimeException::class => 'critical',
        \Illuminate\Database\QueryException::class => 'emergency',
        \Illuminate\Validation\ValidationException::class => 'error',
        \Symfony\Component\HttpKernel\Exception\HttpException::class => 'error',
        \Error::class => 'error',
    ],

    /*
     * -------------------------------------------------------------------------
     * Throttling Configuration
     * -------------------------------------------------------------------------
     */
    'throttling' => [
        'enabled' => env('ERROR_NOTIFIER_THROTTLE_ENABLED', true),
        'default_cooldown_minutes' => 1,
    ],


    /*
     * -------------------------------------------------------------------------
     * Auto Actions Configuration
     * -------------------------------------------------------------------------
     */
    'auto_actions' => [
        'maintenance_enabled' => env('ERROR_NOTIFIER_MAINTENANCE_ENABLED', true),
        'maintenance_cooldown_minutes' => 15,
        'maintenance_secret' => env('ERROR_NOTIFIER_MAINTENANCE_SECRET'),
        'lock_key_prefix' => env('ERROR_NOTIFIER_LOCK_PREFIX', 'error-notifier:lock:'),
        'lock_features' => [
            'emergency' => ['test-lock-route', 'registration'],
        ],
    ],
];