<?php

namespace alhumsi\ErrorNotifier\Tests;

use Orchestra\Testbench\TestCase;
use alhumsi\ErrorNotifier\ErrorNotifierServiceProvider;
use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use alhumsi\ErrorNotifier\Contracts\MessageFormatterInterface;
use alhumsi\ErrorNotifier\Throttler;
use alhumsi\ErrorNotifier\Services\Maintainer;
use alhumsi\ErrorNotifier\Services\FeatureLocker; // Don't forget to import all required classes!
use RuntimeException;
use Mockery;

class ErrorFlowTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ErrorNotifierServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        // Set environment to load error-notifier config with two active channels
        $app['config']->set('error-notifier', [
            'channels' => [
                'slack' => ['webhook_url' => 'http://fake/slack'],
                'telegram' => ['bot_token' => 'fake_token', 'chat_id' => 'fake_id', 'bot_url' => 'http://fake/tele'],
            ],
            'levels' => [
                'emergency' => ['slack', 'telegram'],
            ],
            'analyzers' => [
                \RuntimeException::class => 'emergency',
            ],
            // Add required auto_actions config so Maintainer and FeatureLocker don't crash
            'auto_actions' => [
                'maintenance_enabled' => true,
                'maintenance_cooldown_minutes' => 15,
                'maintenance_secret' => 'TEST_SECRET',
                'lock_features' => ['emergency' => ['test-lock']],
            ],
            'throttling' => ['enabled' => true, 'default_cooldown_minutes' => 1],
        ]);
    }

    /** @test */
    public function exception_triggers_notifier_for_configured_channels()
    {
        // --- BIND ALL 6 DEPENDENCIES (MOCK THE ONES WE DON'T CARE ABOUT) ---

        // 1. MOCK Notifier (The one we are actually testing)
        $mockNotifier = Mockery::mock(NotifierInterface::class);
        $mockNotifier->shouldReceive('send')
            ->times(2)
            ->andReturn(true);

        // 2. MOCK Throttler (Should always allow sending in this test)
        $mockThrottler = Mockery::mock(Throttler::class);
        $mockThrottler->shouldReceive('allowed')->andReturn(true);

        // 3. MOCK Maintainer (Should do nothing but must be available)
        $mockMaintainer = Mockery::mock(Maintainer::class);
        $mockMaintainer->shouldReceive('down')->andReturn(false);

        // 4. MOCK FeatureLocker (Should do nothing but must be available)
        $mockFeatureLocker = Mockery::mock(FeatureLocker::class);
        $mockFeatureLocker->shouldReceive('lock')->andReturn(true);

        // 5. BIND ALL MOCKED SERVICES
        $this->app->instance(NotifierInterface::class, $mockNotifier);
        $this->app->instance(Throttler::class, $mockThrottler);
        $this->app->instance(Maintainer::class, $mockMaintainer);
        $this->app->instance(FeatureLocker::class, $mockFeatureLocker);

        // 6. BIND REAL ANALYZER & FORMATTER (Needed for formatting the payload)
        // Since Analyzer and Formatter are concrete classes, we must bind them
        // using the real implementation or the Listener will crash.
        $this->app->bind(AnalyzerInterface::class, \alhumsi\ErrorNotifier\Analyzer::class);
        $this->app->bind(MessageFormatterInterface::class, \alhumsi\ErrorNotifier\MessageFormatter::class);


        // 7. Simulate the exception being reported
        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $handler->report(new RuntimeException('Test error notification.'));

        // Mockery verifies the expectations when the test finishes
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}