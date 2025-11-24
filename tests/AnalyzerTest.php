<?php

namespace alhumsi\ErrorNotifier\Tests;

use Orchestra\Testbench\TestCase;
use alhumsi\ErrorNotifier\Analyzer;
use RuntimeException;
use PHPUnit\Framework\Attributes\Test;

class AnalyzerTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('error-notifier.analyzers', [
            \RuntimeException::class => 'critical',
        ]);
    }

    #[Test]
    public function it_respects_configured_level_for_generic_exceptions()
    {
        $analyzer = new Analyzer();
        $exception = new RuntimeException('Something went wrong');

        $result = $analyzer->analyze($exception);

        $this->assertEquals('critical', $result['level'], 'Analyzer should respect the configured level for RuntimeException.');
    }
}
