<?php

return [
    /*
     * -------------------------------------------------------------------------
     * Channels & Configuration
     * -------------------------------------------------------------------------
     * Define the channels to use and their connection details.
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
     * Define the default channels to be notified for each severity level.
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
     * Map specific exception classes to a package level (e.g., 'critical')
     */
    'analyzers' => [
        \Illuminate\Database\QueryException::class => 'critical',
        \Illuminate\Validation\ValidationException::class => 'error',
        \Symfony\Component\HttpKernel\Exception\HttpException::class => 'error',
        \TypeError::class => 'critical',
        \ErrorException::class => 'critical',
    ],

    /*
     * -------------------------------------------------------------------------
     * Throttling Configuration
     * -------------------------------------------------------------------------
     * Settings for preventing spam notifications when errors repeat rapidly.
     */
    'throttling' => [
        'enabled' => env('ERROR_NOTIFIER_THROTTLE_ENABLED', true),
        // The default time (in minutes) to silence a repeating error after the first notification.
        'default_cooldown_minutes' => 1,
    ],
];