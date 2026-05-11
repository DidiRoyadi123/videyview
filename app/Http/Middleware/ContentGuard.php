<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BlockedIp;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class ContentGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Check if IP is in blacklist with caching for 1 hour
        try {
            $isBlocked = Cache::remember("blocked_ip_{$ip}", 3600, function() use ($ip) {
                return BlockedIp::where('ip_address', $ip)->exists();
            });
        } catch (\Exception $e) {
            // Fail open for security middleware during maintenance/DB issues
            \Log::warning("ContentGuard DB Error: " . $e->getMessage());
            $isBlocked = false;
        }

        if ($isBlocked) {
            abort(403, 'Access denied. Your connection has been flagged by the VideyView Fortress.');
        }

        return $next($request);
    }
}
