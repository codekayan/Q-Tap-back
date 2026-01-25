<?php

namespace App\Http\Middleware;

use App\Models\qtap_clients;
use App\Models\restaurant_user_staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userType = $request->input('user_type');
        $email = $request->input('email');

        if (!$userType || !$email) {
            return $next($request);
        }

        if ($userType === 'qtap_clients') {
            $query = restaurant_user_staff::where('email', $email);

            if ($request->filled('pin')) {
                $query->where('pin', $request->input('pin'));
            }

            if ($request->filled('brunch_id')) {
                $query->where('brunch_id', $request->input('brunch_id'));
            }

            $staff = $query->first();

            if ($staff) {
                $client = qtap_clients::find($staff->user_id);

                if ($client && $this->isInactive($client->getAttribute('status'))) {
                    return response()->json(['error' => 'User is not active'], 401);
                }
            }
        }

        return $next($request);
    }

    private function isInactive($status): bool
    {
        if ($status === null) {
            return false;
        }

        if (is_string($status)) {
            return strtolower($status) !== 'active';
        }

        return !$status;
    }
}

