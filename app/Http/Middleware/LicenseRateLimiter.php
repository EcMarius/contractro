<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LicenseRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $licenseKey = $request->input('license_key');

        // Rate limit by IP: 100 requests per hour
        $ipKey = 'license_validate_ip:' . $ip;
        $ipAttempts = RateLimiter::attempt($ipKey, 100, function () {
            return true;
        }, 3600);

        if (!$ipAttempts) {
            $availableIn = RateLimiter::availableIn($ipKey);

            // Log suspicious activity
            \Log::warning('License validation rate limit exceeded', [
                'ip' => $ip,
                'license_key' => $licenseKey,
                'available_in' => $availableIn,
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Too many validation attempts. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $availableIn,
            ], 429);
        }

        // Rate limit by license key: 1000 checks per day (if provided)
        if ($licenseKey) {
            $licenseKeyKey = 'license_validate_key:' . $licenseKey;
            $keyAttempts = RateLimiter::attempt($licenseKeyKey, 1000, function () {
                return true;
            }, 86400);

            if (!$keyAttempts) {
                // Log potential abuse
                \Log::warning('License key validation limit exceeded', [
                    'ip' => $ip,
                    'license_key' => $licenseKey,
                    'available_in' => RateLimiter::availableIn($licenseKeyKey),
                ]);

                return response()->json([
                    'valid' => false,
                    'message' => 'This license has exceeded daily validation limit.',
                    'code' => 'LICENSE_RATE_LIMIT_EXCEEDED',
                ], 429);
            }
        }

        // Track failed attempts per IP for brute force detection
        if ($request->isMethod('post')) {
            $response = $next($request);

            // If validation failed, increment fail counter
            if ($response->getStatusCode() === 403 ||
                (method_exists($response, 'getData') &&
                 isset($response->getData()->valid) &&
                 !$response->getData()->valid)) {

                $failKey = 'license_validate_fails:' . $ip;
                $fails = Cache::increment($failKey, 1);

                if ($fails === 1) {
                    Cache::put($failKey, 1, 3600); // Expire in 1 hour
                }

                // After 20 failed attempts in an hour, add extra throttling
                if ($fails > 20) {
                    \Log::alert('Potential brute force attack on license validation', [
                        'ip' => $ip,
                        'fails' => $fails,
                        'user_agent' => $request->userAgent(),
                    ]);

                    // Block for 10 minutes after 20 fails
                    if ($fails > 20 && $fails % 5 === 0) {
                        Cache::put('license_validate_block:' . $ip, true, 600);
                    }
                }
            }

            return $response;
        }

        // Check if IP is temporarily blocked
        if (Cache::has('license_validate_block:' . $ip)) {
            return response()->json([
                'valid' => false,
                'message' => 'Temporarily blocked due to suspicious activity.',
                'code' => 'IP_BLOCKED',
            ], 403);
        }

        return $next($request);
    }
}
