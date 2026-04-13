<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && $request->user()->is_admin && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            try {
                \App\Models\AdminActivity::create([
                    'user_id' => $request->user()->id,
                    'action' => $request->method() . ' ' . $request->path(),
                    'description' => json_encode($request->except(['password', 'password_confirmation', '_token'])),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to log admin activity: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
