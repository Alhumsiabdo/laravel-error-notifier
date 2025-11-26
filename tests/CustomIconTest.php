<?php

namespace alhumsi\ErrorNotifier\Tests;

use Orchestra\Testbench\TestCase;
use alhumsi\ErrorNotifier\MessageFormatter;
use Illuminate\Support\Facades\Config;

class CustomIconTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\alhumsi\ErrorNotifier\ErrorNotifierServiceProvider::class];
    }

    /** @test */
    public function it_uses_default_icon_when_not_configured()
    {
        Config::set('error-notifier.icons', []);
        
        $formatter = new MessageFormatter();
        $report = [
            'level' => 'critical',
            'type' => 'TestError',
            'summary' => 'Something went wrong',
            'suggestion' => 'Fix it',
            'context' => [],
        ];

        $result = $formatter->format($report, 'slack');
        
        $this->assertStringContainsString('🚨', $result['blocks'][0]['text']['text']);
    }

    /** @test */
    public function it_uses_custom_icon_when_configured()
    {
        Config::set('error-notifier.icons.critical', '💀');

        $formatter = new MessageFormatter();
        $report = [
            'level' => 'critical',
            'type' => 'TestError',
            'summary' => 'Something went wrong',
            'suggestion' => 'Fix it',
            'context' => [],
        ];

        $result = $formatter->format($report, 'slack');
        
        $this->assertStringContainsString('💀', $result['blocks'][0]['text']['text']);
    }
}
