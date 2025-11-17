## Examples

### 1. Route-Level Smoke Test
```php
Route::get('/boom', function () {
    throw new RuntimeException('Intentional failure to test Error Notifier');
});
```
Hit `/boom` in your browser. The package intercepts the exception inside `ExceptionListener`, analyzes it, formats a Markdown payload, and posts to every channel mapped to `emergency`.

### 2. Custom Analyzer Implementation
```php
namespace App\Support;

use alhumsi\ErrorNotifier\Contracts\AnalyzerInterface;
use Throwable;

class PaymentAnalyzer implements AnalyzerInterface
{
    public function analyze(Throwable $exception): array
    {
        return [
            'level' => 'critical',
            'type' => 'payment_gateway',
            'summary' => $exception->getMessage(),
            'context' => [
                'gateway' => 'Stripe',
                'user_id' => optional(auth()->user())->id,
            ],
            'suggestion' => 'Check gateway logs and the recent charge.',
        ];
    }
}
```

Bind it in a service provider:
```php
$this->app->singleton(
    alhumsi\ErrorNotifier\Contracts\AnalyzerInterface::class,
    App\Support\PaymentAnalyzer::class
);
```

### 3. Queue-Based Notifier
```php
namespace App\Support;

use alhumsi\ErrorNotifier\Contracts\NotifierInterface;
use App\Jobs\SendErrorNotificationJob;

class QueuedNotifier implements NotifierInterface
{
    public function send(array $payload, string $channel): bool
    {
        SendErrorNotificationJob::dispatch($payload, $channel);
        return true;
    }
}
```
Inside the job, reuse the default notifier or invoke a third-party SDK. Bind this class to `NotifierInterface` to offload HTTP calls to the queue.

### 4. Manual Invocation (e.g., Cron Jobs)
If you catch exceptions manually and still want alerts:
```php
try {
    // risky code
} catch (Throwable $e) {
    app(alhumsi\ErrorNotifier\Listeners\ExceptionListener::class)->handle($e);
}
```

### 5. Testing Customizations
Leverage Mockery + Testbench (see `tests/ErrorFlowTest.php`) to assert your bindings:
```php
$mock = Mockery::mock(alhumsi\ErrorNotifier\Contracts\NotifierInterface::class);
$mock->shouldReceive('send')->twice()->andReturnTrue();
$this->app->instance(alhumsi\ErrorNotifier\Contracts\NotifierInterface::class, $mock);

$handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
$handler->report(new RuntimeException('test'));
```

These scenarios cover the most common integration questions. Adapt and combine them as needed.

