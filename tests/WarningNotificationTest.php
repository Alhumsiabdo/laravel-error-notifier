<?php

namespace alhumsi\ErrorNotifier\Tests;

use Orchestra\Testbench\TestCase;
use alhumsi\ErrorNotifier\ErrorNotifierServiceProvider;
use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use Mockery;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class WarningNotificationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ErrorNotifierServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('error-notifier.channels', [
            'slack' => ['webhook_url' => 'http://fake/slack'],
        ]);
        
        $app['config']->set('error-notifier.levels', [
            'warning' => ['slack'],
        ]);
    }

    #[Test]
    public function it_sends_notification_for_warning_level()
    {
        // Mock Notifier
        $mockNotifier = Mockery::mock(NotifierInterface::class);
        $mockNotifier->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return str_contains($payload['text'], 'Test warning message');
            }), 'slack')
            ->andReturn(true);

        $this->app->instance(NotifierInterface::class, $mockNotifier);

        // Trigger warning
        Log::warning('Test warning message');
        
        $this->assertTrue(true); // Mockery assertion is handled internally, but this satisfies PHPUnit's risk check
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
