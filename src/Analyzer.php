<?php

namespace Alhumsi\ErrorNotifier;

use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Analyzer implements AnalyzerInterface
{
    public function analyze(Throwable $exception): array
    {
        $analysis = $this->analyzeKnownExceptions($exception);

        if (empty($analysis)) {
            $analysis = $this->analyzeGenericException($exception);
        }

        return $analysis;
    }

    /**
     * Finds the severity levels configured for the given exception class.
     */
    protected function getLevelFromConfig(string $exceptionClass): ?string
    {
        return config("error-notifier.analyzers.{$exceptionClass}");
    }

    protected function analyzeKnownExceptions(Throwable $e): array
    {
        $exceptionClass = get_class($e);
        $level = $this->getLevelFromConfig($exceptionClass);
        if (!$level) {
            if ($e instanceof \Error) {
                $level = $this->getLevelFromConfig(\Error::class);
            } elseif ($e instanceof \ErrorException) {
                $level = $this->getLevelFromConfig(\ErrorException::class);
            }
        }
        if (!$level) {
            return [];
        }

        if ($e instanceof ValidationException) {
            return [
                'level' => $level, // USES LEVEL FROM CONFIG
                'type' => 'validation',
                'summary' => 'Form validation failed for user input.',
                'context' => ['errors' => $e->errors(), 'input' => request()->all()],
                'suggestion' => 'Check validation rules and user input values.'
            ];
        }
        if ($e instanceof QueryException) {
            return [
                'level' => $level,
                'type' => 'database',
                'summary' => 'A database query failed unexpectedly.',
                'context' => ['sql' => $e->getSql(), 'bindings' => $e->getBindings()],
                'suggestion' => 'Review the SQL query and ensure database connection/schema are correct.'
            ];
        }
        if ($e instanceof HttpException && $e->getStatusCode() >= 500) {
            return [
                'level' => $level,
                'type' => 'http_server',
                'summary' => 'A 5xx HTTP error occurred.',
                'context' => ['status' => $e->getStatusCode()],
                'suggestion' => 'Check upstream services or internal API endpoints.'
            ];
        }
        if ($e instanceof \Error || $e instanceof \ErrorException) {
            return [
                'level' => $level,
                'type' => 'php_fatal',
                'summary' => "Mapped PHP Fatal Error: {$e->getMessage()}",
                'context' => ['error_class' => $exceptionClass],
                'suggestion' => 'Review the function call arguments for type mismatch.',
            ];
        }

        // Fallback for any other exception that has a configured level but no specific handler
        return [
            'level' => $level,
            'type' => 'configured_exception',
            'summary' => "Exception: {$e->getMessage()}",
            'context' => ['class' => $exceptionClass, 'file' => $e->getFile(), 'line' => $e->getLine()],
            'suggestion' => 'Check the error message and stack trace.'
        ];
    }

    protected function analyzeGenericException(Throwable $e): array
    {
        $className = get_class($e);
        return [
            'level' => 'emergency',
            'type' => 'unhandled',
            'summary' => "Unhandled runtime error: {$e->getMessage()}",
            'context' => ['file' => $e->getFile(), 'line' => $e->getLine()],
            'suggestion' => "Review the stack trace for the {$className} exception."
        ];
    }
}