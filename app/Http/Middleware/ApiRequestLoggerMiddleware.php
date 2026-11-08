<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLoggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $payload = $request->all();
        if (isset($payload['password'])) {
            $payload['password'] = '******';
        }
        if (isset($payload['password_confirmation'])) {
            $payload['password_confirmation'] = '******';
        }

        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'request' => $payload,
        ];

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 500) {
            Log::error('API ERROR [5xx]: ' . $request->method() . ' ' . $request->path(), $logData);
        } elseif ($statusCode >= 400) {
            Log::warning('API WARNING [4xx]: ' . $request->method() . ' ' . $request->path(), $logData);
        } else {
            Log::info('API SUCCESS [2xx]: ' . $request->method() . ' ' . $request->path(), $logData);
        }

        return $response;
    }
}
