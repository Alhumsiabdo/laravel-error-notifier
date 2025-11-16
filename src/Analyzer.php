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
        // 1. Check for specific, known exception types (Validation, DB, HTTP)
        $analysis = $this->analyzeKnownExceptions($exception);

        // 2. If not a known type, fall back to a generic analysis
        if (empty($analysis)) {
            $analysis = $this->analyzeGenericException($exception);
        }

        return $analysis;
    }

    protected function analyzeKnownExceptions(Throwable $e): array
    {
        if ($e instanceof ValidationException) {
            return [
                'level' => 'error',
                'type' => 'validation',
                'summary' => 'Form validation failed for user input.',
                'context' => ['errors' => $e->errors(), 'input' => request()->all()],
                'suggestion' => 'Check validation rules and user input values.'
            ];
        }

        if ($e instanceof QueryException) {
            return [
                'level' => 'critical',
                'type' => 'database',
                'summary' => 'A database query failed unexpectedly.',
                'context' => ['sql' => $e->getSql(), 'bindings' => $e->getBindings()],
                'suggestion' => 'Review the SQL query and ensure database connection/schema are correct.'
            ];
        }

        if ($e instanceof HttpException && $e->getStatusCode() >= 500) {
            return [
                'level' => 'error',
                'type' => 'http_server',
                'summary' => 'A 5xx HTTP error occurred.',
                'context' => ['status' => $e->getStatusCode()],
                'suggestion' => 'Check upstream services or internal API endpoints.'
            ];
        }

        return [];
    }

    protected function analyzeGenericException(Throwable $e): array
    {
        $className = get_class($e);
        return [
            'level' => 'emergency', // Defaulting to high severity for unhandled types
            'type' => 'unhandled',
            'summary' => "Unhandled runtime error: {$e->getMessage()}",
            'context' => ['file' => $e->getFile(), 'line' => $e->getLine()],
            'suggestion' => "Review the stack trace for the {$className} exception."
        ];
    }
}