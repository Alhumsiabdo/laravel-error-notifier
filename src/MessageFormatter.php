<?php

namespace alhumsi\ErrorNotifier;

use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;

class MessageFormatter implements MessageFormatterInterface
{
    public function format(array $report, string $channel): array
    {
        $markdown = $this->generateMarkdown($report);

        // Based on the channel, return the required structure.
        return match ($channel) {
            'slack' => $this->formatForSlack($markdown, $report),
            'telegram' => $this->formatForTelegram($markdown, $report),
            default => throw new \InvalidArgumentException("Unsupported channel: {$channel}"),
        };
    }

    protected function generateMarkdown(array $report): string
    {
        $level = strtoupper($report['level'] ?? 'UNKNOWN');
        $type = $report['type'] ?? 'General Error';
        $summary = $report['summary'] ?? 'No summary available.';
        $suggestion = $report['suggestion'] ?? 'No specific suggestion provided.';
        $timestamp = now()->toDateTimeString();
        $env = app()->environment();

        $template = "🚨 *{$level}* — {$type}\n\n" .
            "*Summary:* {$summary}\n" .
            "*Context:*\n" .
            "- *Env:* `{$env}`\n" .
            "- *Time:* `{$timestamp}`\n" .
            "- *Suggestion:* {$suggestion}";

        // Add context details if they exist (simple list for MVP)
        if (!empty($report['context'])) {
            $template .= "\n*Details:* " . json_encode($report['context'], JSON_PRETTY_PRINT);
        }
        return $template;
    }

    // Slack requires blocks for rich formatting
    protected function formatForSlack(string $markdown, array $report): array
    {
        return [
            'text' => $report['summary'], // Fallback text
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $markdown,
                    ],
                ],
            ],
        ];
    }

    // Telegram often takes the text directly
    protected function formatForTelegram(string $markdown, array $report): array
    {
        return [
            'text' => $markdown,
            'parse_mode' => 'Markdown',
        ];
    }
}