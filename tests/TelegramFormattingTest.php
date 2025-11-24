<?php

namespace alhumsi\ErrorNotifier\Tests;

use Orchestra\Testbench\TestCase;
use alhumsi\ErrorNotifier\MessageFormatter;
use PHPUnit\Framework\Attributes\Test;

class TelegramFormattingTest extends TestCase
{
    #[Test]
    public function it_escapes_special_characters_in_telegram_fields()
    {
        $formatter = new MessageFormatter();
        
        $report = [
            'level' => 'critical_error', // Contains underscore
            'type' => 'php_fatal_error', // Contains underscore
            'summary' => 'Something went wrong',
            'context' => [],
            'suggestion' => 'Fix it',
        ];

        $result = $formatter->format($report, 'telegram');
        $text = $result['text'];

        // Check if underscores are escaped
        $this->assertStringContainsString('CRITICAL\_ERROR', $text, 'Level should be escaped');
        $this->assertStringContainsString('php\_fatal\_error', $text, 'Type should be escaped');
    }
}
