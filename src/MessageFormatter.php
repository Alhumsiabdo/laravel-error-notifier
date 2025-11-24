<?php

namespace alhumsi\ErrorNotifier;

use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;
use InvalidArgumentException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Carbon;

class MessageFormatter implements MessageFormatterInterface
{
    public function format(array $report, string $channel): array
    {
        $markdown = $this->generateMarkdown($report);

        // Based on the channel, return the required structure.
        return match ($channel) {
            'slack' => $this->formatForSlack($markdown, $report),
            'telegram' => $this->formatForTelegram($markdown, $report),
            'discord' => $this->formatForDiscord($markdown, $report),
            default => throw new InvalidArgumentException("Unsupported channel: {$channel}"),
        };
    }

    protected function generateMarkdown(array $report): string
    {
        $level = $this->escapeMarkdown(strtoupper($report['level'] ?? 'UNKNOWN'));
        $type = $this->escapeMarkdown($report['type'] ?? 'General Error');

        // Escape summary and suggestion before embedding them
        $summary = $this->escapeMarkdown($report['summary'] ?? 'No summary available.');
        $suggestion = $this->escapeMarkdown($report['suggestion'] ?? 'No specific suggestion provided.');

        $timestamp = Carbon::now()->toDateTimeString();
        $env = $this->escapeMarkdown(App::environment());

        // FIX: Replaced list markers from '-' to '•' to avoid Telegram MarkdownV2 reserved character issue.
        $template = "🚨 *{$level}* — {$type}\n\n" .
            "*Summary:* {$summary}\n" .
            "*Context:*\n" .
            "• *Env:* `{$env}`\n" .
            "• *Time:* `{$timestamp}`\n" .
            "• *Suggestion:* {$suggestion}";

        // Add context details, escaping the JSON payload before wrapping in code block
        if (!empty($report['context'])) {
            $json_context = json_encode($report['context'], JSON_PRETTY_PRINT);

            // Apply escaping to the JSON block contents
            $template .= "\n*Details:* \n```json\n" . $this->escapeMarkdown($json_context) . "\n```";
        }
        return $template;
    }

    /**
     * Escapes special Markdown V2 characters (Telegram) within text strings.
     */
    protected function escapeMarkdown(string $text): string
    {
        // Characters to escape: _, *, [, ], (, ), ~, `, >, #, +, -, =, |, {, }, ., !
        // This is necessary for dynamic content (like error messages) that is not part of explicit Markdown syntax.
        $replacements = [
            '\\' => '\\\\',
            '_' => '\\_',
            '*' => '\\*',
            '[' => '\\[',
            ']' => '\\]',
            '(' => '\\(',
            ')' => '\\)',
            '~' => '\\~',
            '`' => '\\`',
            '>' => '\\>',
            '#' => '\\#',
            '+' => '\\+',
            '-' => '\\-', // This is necessary for dynamic data containing hyphens
            '=' => '\\=',
            '|' => '\\|',
            '{' => '\\{',
            '}' => '\\}',
            '.' => '\\.',
            '!' => '\\!',
        ];

        // Apply replacements, ensuring backslashes are handled first
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    protected function formatForSlack(string $markdown, array $report): array
    {
        return [
            'text' => $report['summary'],
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

    protected function formatForTelegram(string $markdown, array $report): array
    {
        return [
            'text' => $markdown,
            'parse_mode' => 'MarkdownV2', // FIX: Explicitly using MarkdownV2
        ];
    }

    protected function formatForDiscord(string $markdown, array $report): array
    {
        return [
            'content' => $markdown,
        ];
    }
}