<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class DashboardCacheMiddleware
{
    /**
     * Handle dashboard caching and cache control
     */
    public function handle(Request $request, Closure $next, $cacheTime = 300): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        // Skip caching for certain requests
        if ($this->shouldSkipCache($request)) {
            return $next($request);
        }

        $cacheKey = $this->generateCacheKey($request, $user);

        // Check if we have cached response
        if ($request->isMethod('GET') && Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);

            return response()->json(array_merge($cachedData, [
                'cached' => true,
                'cache_time' => $cachedData['cached_at'] ?? null,
            ]));
        }

        $response = $next($request);

        // Cache successful responses
        if ($response->isSuccessful() && $request->isMethod('GET')) {
            $data = $response->getData(true);
            $data['cached_at'] = now()->toISOString();

            Cache::put($cacheKey, $data, (int) $cacheTime);
        }

        return $response;
    }

    /**
     * Determine if caching should be skipped
     */
    private function shouldSkipCache(Request $request): bool
    {
        // Skip caching for these conditions
        return $request->has('refresh') ||
               $request->has('no-cache') ||
               $request->isMethod('POST') ||
               $request->isMethod('PUT') ||
               $request->isMethod('DELETE') ||
               str_contains($request->path(), 'export') ||
               str_contains($request->path(), 'realtime');
    }

    /**
     * Generate cache key for request
     */
    private function generateCacheKey(Request $request, $user): string
    {
        $keyParts = [
            'dashboard',
            $user->id,
            $user->getRoleNames()->first(),
            md5($request->getRequestUri()),
            md5(serialize($request->query->all())),
        ];

        return implode('_', array_filter($keyParts));
    }
}
