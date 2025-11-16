<?php

namespace alhumsi\ErrorNotifier\Contracts;

interface MessageFormatterInterface
{
    /**
     * Formats the analyzed report into a channel-specific payload.
     *
     * @param array $report The structured data from the Analyzer.
     * @param string $channel The target channel (e.g., 'slack', 'telegram').
     * @return array The payload ready for the notification channel.
     */
    public function format(array $report, string $channel): array;
}