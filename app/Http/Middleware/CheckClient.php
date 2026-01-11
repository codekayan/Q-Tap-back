<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\restaurant_users;
use App\Models\restaurant_staff;
use App\Models\qtap_clients_brunchs;
use App\Models\role;
use Illuminate\Support\Facades\Auth;

class CheckClient
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // تحديد الحارس الصحيح
        Auth::shouldUse('qtap_clients');

        try {
            $user = Auth::guard('qtap_clients')->user();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized. Token is invalid or expired.'], Response::HTTP_UNAUTHORIZED);
        }



        // البحث عن العميل في `qtap_clients_brunchs`
        $client = qtap_clients_brunchs::where('client_id', $user->id)->first();

        if (!$client) {
            return response()->json(['message' => 'Unauthorized. Only clients are allowed.'], Response::HTTP_FORBIDDEN);
        }

        // التحقق من دور الموظف إذا تم تحديد أدوار
        if (!empty($roles)) {

            dd($user);


            // البحث عن الموظف في `restaurant_users` لمعرفة الفرع الذي يعمل به
            $employee = restaurant_users::where('name', $user->name) // البحث بالاسم وليس user_id
            ->where('brunch_id', $client->brunch_id) // التأكد أن الموظف يعمل في نفس الفرع
                ->first();

            if (!$employee) {
                return response()->json(['message' => 'Forbidden. You are not assigned to this branch.'], Response::HTTP_FORBIDDEN);
            }

            // البحث عن بيانات الموظف في `restaurant_staff` لمعرفة `role_id`
            $staff = restaurant_staff::where('user_id', $employee->id)->first();

            if (!$staff) {
                return response()->json(['message' => 'Forbidden. Employee role not found.'], Response::HTTP_FORBIDDEN);
            }

            // جلب اسم الدور من `role`
            $role = role::where('id', $staff->role_id)->value('name');

            if (!$role || !in_array($role, $roles)) {
                return response()->json(['message' => 'Forbidden. You do not have the required role.'], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
