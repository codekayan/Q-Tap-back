<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOrDeliveryRider
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, ['admin', 'delivery_rider'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return $next($request);
    }
}
