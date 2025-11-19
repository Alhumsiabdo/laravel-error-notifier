<?php

namespace alhumsi\ErrorNotifier\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use alhumsi\ErrorNotifier\Services\FeatureLocker;

class CheckFeatureLock
{
    protected FeatureLocker $locker;

    public function __construct(FeatureLocker $locker)
    {
        $this->locker = $locker;
    }

    /**
     * Handle an incoming request.
     * @param string $featureName The feature name passed via the route middleware definition (e.g., 'checkout').
     */
    public function handle(Request $request, Closure $next, string $featureName): Response
    {
        if ($this->locker->isLocked($featureName)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => "The feature '{$featureName}' is temporarily disabled due to a system issue.",
                ], 503);
            }
            return response()->view('errors::feature-locked', [
                'feature' => $featureName
            ], 503);
        }
        return $next($request);
    }
}