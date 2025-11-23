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
        ]);
    }

    public function test_exception_triggers_notifier_for_configured_channels()
    {
        $mockNotifier = Mockery::mock(NotifierInterface::class);
        $mockNotifier->shouldReceive('send')
            ->times(2)
            ->withArgs(function ($payload, $channel) {
                return in_array($channel, ['slack', 'telegram']);
            })
            ->andReturn(true); // Mock a successful send

        $this->app->instance(NotifierInterface::class, $mockNotifier);

        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $handler->report(new RuntimeException('Test error notification.'));

        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}