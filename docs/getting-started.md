## Getting Started

This guide walks through the full setup of `laravel-error-notifier`, from installation to validation.

### 1. Install & Publish
```bash
composer require alhumsi/laravel-error-notifier
php artisan vendor:publish --tag=error-notifier-config
```

Publishing creates `config/error-notifier.php`. Commit this file so the team shares defaults.

### 2. Configure Channels
Update `.env` with the credentials you plan to use. Each channel is optional—leave the environment variables unset to skip it.

```dotenv
ERROR_NOTIFIER_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx/yyy/zzz
ERROR_NOTIFIER_TELEGRAM_BOT_TOKEN=123456:ABC
ERROR_NOTIFIER_TELEGRAM_CHAT_ID=123456789
ERROR_NOTIFIER_DISCORD_WEBHOOK=https://discord.com/api/webhooks/xxx/yyy
```

### 3. Tune Severity Routing
Open `config/error-notifier.php` and specify which levels go to which channels:
```php
'levels' => [
    'emergency' => ['slack', 'telegram', 'discord'],
    'critical' => ['slack'],
    'error' => ['slack'],
    'warning' => [],
],
```

When the analyzer returns `level => 'critical'`, the package automatically iterates over the configured channels. Empty arrays mean “ignore this severity.”

### 4. Map Exception Classes
Assign classes to severity levels for instant routing:
```php
'analyzers' => [
    \Illuminate\Database\QueryException::class => 'critical',
    \DomainException::class => 'error',
],
```

Unhandled classes fall back to the analyzer’s default (currently `emergency`).

### 5. Verify Delivery
1. Trigger a test exception (e.g., throw `new RuntimeException('Notifier test');` inside a route or command).
2. Watch Slack/Telegram/Discord for the message.
3. Tail the Laravel log; the package logs success/failure per channel.

Tip: use tools like `ngrok` or localtunnel if your bot/webhook needs to reach a local server.

### 6. Production Checklist
- [ ] Environment variables set on every environment.
- [ ] Config cached (`php artisan config:cache`).
- [ ] Queue workers in place if you swap the notifier for queued jobs.
- [ ] Tests passing: `composer test`.

You’re done! Move on to `docs/examples.md` for concrete integration patterns.

