<?php

namespace alhumsi\ErrorNotifier;

use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use Illuminate\Support\Facades\Http;

class Notifier implements NotifierInterface
{
    public function send(array $payload, string $channel): bool
    {
        $url = $this->getWebhookUrl($channel);

        if (!$url) {
            // Log a warning if the URL is missing but was configured to send
            logger()->warning("ErrorNotifier: Skipping '{$channel}' notification. Webhook URL is missing.");
            return false;
        }

        $response = Http::post($url, $payload);

        // Check for success status codes (e.g., 200, 202, 204)
        if ($response->successful()) {
            return true;
        }

        logger()->error("ErrorNotifier: Failed to send '{$channel}' notification.", [
            'status' => $response->status(),
            'response' => $response->body()
        ]);
        return false;
    }

    protected function getWebhookUrl(string $channel): ?string
    {
        return match ($channel) {
            'slack' => config('services.slack.webhook_url'),
            'telegram' => config('services.telegram.bot_url') . '/sendMessage?chat_id=' . config('services.telegram.chat_id'),
            'discord' => config('services.discord.webhook_url'),
            default => null,
        };
    }
}