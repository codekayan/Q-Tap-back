<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectUserType
{
    public function handle(Request $request, Closure $next): Response
    {
        $guards = [
            'qtap_clients',
            'qtap_admins',
            'qtap_affiliate',
            'restaurant_user_staff',
            'delivery_rider',
        ];


        foreach ($guards as $guard) {
            if (auth($guard)->check()) {
                $user = auth($guard)->user();

                return response()->json([
                    'user_type' => $guard,
                    'user_id' => $user->id ?? null,
                    'role' => $user->role ?? null,
                    'name' => $user->name ?? null,
                ]);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
