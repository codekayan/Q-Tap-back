<?php

namespace App\Http\Controllers;

use App\Models\restaurant_user_staff;
use App\Models\qtap_clients_brunchs;
use App\Models\role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;


class RestaurantUserStaffController extends Controller
{

    public function index($brunch_id)
    {


        $brunch = qtap_clients_brunchs::where('status', 'active')->find($brunch_id);

        if (!$brunch) {
            return response()->json([
                'message' => 'Brunch not active',
            ]);
        }


        $restaurant_user_staff = restaurant_user_staff::where('brunch_id', $brunch_id)->whereNotNull('role_id')->get();

        return response()->json([
            'success' => true,
            'restaurant_user_staff' => $restaurant_user_staff
        ]);
    }

    public function resturant_users($brunch_id)
    {

        $brunch = qtap_clients_brunchs::where('status', 'active')->find($brunch_id);

        if (!$brunch) {
            return response()->json([
                'message' => 'Brunch not active',
            ]);
        }


        $restaurant_user_staff = restaurant_user_staff::where('brunch_id', $brunch_id)->get();

        return response()->json([
            'success' => true,
            'resturant_users' => $restaurant_user_staff
        ]);
    }


    public function store(Request $request)
    {

        $user = auth()->user();

        $request['email'] = $user->email;
        $request['password'] = $user->password;
        $request['user_type'] = $user->user_type;
        $request['user_id'] = $user->user_id;




        $validated = validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_id' => 'required|exists:qtap_clients,id',
            'pin' => [
                'required',
                'integer',
                'digits_between:1,6',
                Rule::unique('restaurant_user_staffs', 'pin')->where(function ($query) use ($request) {
                    return $query->where('brunch_id', $request->brunch_id);
                }),
            ],
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
            'user_type' => 'required|string|in:qtap_admin,qtap_clients,qtap_affiliate',

            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
        ]);



        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validated->errors()
            ]);
        }



        // تشفير كلمة المرور قبل الحفظ
        $data = $request->all();
        // $data['pin'] = Hash::make($request->pin);

        // إنشاء المستخدم
        $restaurant_user_staff = restaurant_user_staff::create($data);

        return response()->json([
            'success' => true,
            'restaurant_user_staff' => $restaurant_user_staff
        ]);
    }


    public function update(Request $request, $id)
    {
        $restaurant_user_staff = restaurant_user_staff::find($id);

        if (!$restaurant_user_staff) {
            return response()->json([
                'message' => 'User not found',
            ]);
        }


        $validated = validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'user_id' => 'sometimes|exists:qtap_clients,id',
            'pin' => [
                'sometimes',
                'integer',
                'digits_between:1,6',
                Rule::unique('restaurant_user_staffs', 'pin')
                    ->where(function ($query) use ($request, $restaurant_user_staff) {
                        return $query->where('brunch_id', $request->brunch_id ?? $restaurant_user_staff->brunch_id);
                    })
                    ->ignore($restaurant_user_staff->id),
            ],
            'email' => 'sometimes|string|email|max:255',
            'password' => 'sometimes|string',
            'user_type' => 'sometimes|string|in:qtap_admin,qtap_clients,qtap_affiliate',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
        ]);


        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validated->errors()
            ]);
        }



        $restaurant_user_staff->update($request->all());

        return response()->json([
            'success' => true,
            'restaurant_user_staff' => $restaurant_user_staff
        ]);
    }



    public function link_user_role(Request $request, $id)
    {
        $restaurant_user_staff = restaurant_user_staff::find($id);

        if (!$restaurant_user_staff) {
            return response()->json([
                'message' => 'User not found',
            ]);
        }


        $validated = validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validated->errors()
            ]);
        }
        $role = role::find($request->role_id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ]);
        }

        $request->merge(['role' => $role->name]);


        $restaurant_user_staff->update($request->all());

        return response()->json([
            'success' => true,
            'restaurant_user_staff' => $restaurant_user_staff
        ]);
    }


    public function destroy($id)
    {
        $restaurant_user_staff = restaurant_user_staff::find($id);

        if (!$restaurant_user_staff) {
            return response()->json([
                'message' => 'User not found',
            ]);
        }

        $restaurant_user_staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}
