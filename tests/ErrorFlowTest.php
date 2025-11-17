<?php

namespace alhumsi\ErrorNotifier\Tests;

use Orchestra\Testbench\TestCase;
use alhumsi\ErrorNotifier\ErrorNotifierServiceProvider;
use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use RuntimeException;
use Mockery;

class ErrorFlowTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ErrorNotifierServiceProvider::class];
    }

    /**
     * Define environment setup to simulate package configuration.
     * This loads our custom config file.
     */
    protected function defineEnvironment($app)
    {
        // 2. Set environment to load error-notifier config with two active channels
        $app['config']->set('error-notifier', [
            'channels' => [
                'slack' => ['webhook_url' => 'http://fake/slack'],
                'telegram' => ['bot_token' => 'fake_token', 'chat_id' => 'fake_id', 'bot_url' => 'http://fake/tele'],
            ],
            // Map RuntimeException (generic exception) to two channels
            'levels' => [
                'emergency' => ['slack', 'telegram'],
            ],
            'analyzers' => [
                // Analyzer will use this to determine the level
                \RuntimeException::class => 'emergency',
            ],
        ]);
    }

    public function test_exception_triggers_notifier_for_configured_channels()
    {
        // 3. Mock the NotifierInterface
        $mockNotifier = Mockery::mock(NotifierInterface::class);

        // 4. Set the expectation: The send method should be called exactly twice
        // (once for 'slack', once for 'telegram', as configured above).
        $mockNotifier->shouldReceive('send')
            ->times(2)
            ->withArgs(function ($payload, $channel) {
                // Optional: Assert the channels used
                return in_array($channel, ['slack', 'telegram']);
            })
            ->andReturn(true); // Mock a successful send

        // 5. Bind the mock to the service container
        $this->app->instance(NotifierInterface::class, $mockNotifier);

        // 6. Simulate the exception being reported
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