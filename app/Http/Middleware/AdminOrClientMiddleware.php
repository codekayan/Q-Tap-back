<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOrClientMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (auth('qtap_admins')->check()) {
            return $next($request);
        }

        $staffUser = auth('restaurant_user_staff')->user();
        if ($staffUser && $staffUser->role === 'admin') {
            return $next($request);
        }

        return redirect()->route('login');
    }
}
