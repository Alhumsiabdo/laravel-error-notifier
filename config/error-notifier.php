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
            // Get webhook URL directly from environment for security
            'webhook_url' => env('ERROR_NOTIFIER_SLACK_WEBHOOK'),
        ],
        'telegram' => [
            // Credentials needed to build the sendMessage endpoint
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
        // Example of a built-in mapping:
        \Illuminate\Database\QueryException::class => 'critical',
        \Illuminate\Validation\ValidationException::class => 'error',
        \Symfony\Component\HttpKernel\Exception\HttpException::class => 'error', // 4xx/5xx errors
        \TypeError::class => 'critical',
        \ErrorException::class => 'critical',

        // You can later add custom analyzers here
    ],
];