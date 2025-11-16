<?php

namespace alhumsi\ErrorNotifier\Contracts;

interface NotifierInterface
{
    /**
     * Sends the formatted payload to the specified channel.
     *
     * @param array $payload The channel-specific data payload.
     * @param string $channel The target channel ('slack' or 'telegram').
     * @return bool True on success, false otherwise.
     */
    public function send(array $payload, string $channel): bool;
}