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
        $config = config("error-notifier.channels.{$channel}");

        if (!$config) {
            return null;
        }

        return match ($channel) {
            'slack', 'discord' => $config['webhook_url'] ?? null,
            'telegram' => ($config['bot_url'] ?? '') . ($config['bot_token'] ?? '') . '/sendMessage?chat_id=' . ($config['chat_id'] ?? ''),
            default => null,
        };
    }
}