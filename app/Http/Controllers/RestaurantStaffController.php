<?php

namespace App\Http\Controllers;

use App\Models\role;
use App\Models\restaurant_staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RestaurantStaffController extends Controller
{

    public function index(Request $request)
    {
        $restaurant_staff = restaurant_staff::where('brunch_id', $request->brunch_id)->get();
        return response()->json([
            'success' => true,
            'restaurant_staff' => $restaurant_staff
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|unique:restaurant_staffs,user_id|exists:restaurant_users,id',
            'role' => 'required|string|exists:roles,name',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = role::where('name', $request->role)->first();
        $request->merge(['role_id' => $role->id]);

        $data = $request->all();
        $restaurant_staff = restaurant_staff::create($data);

        return response()->json([
            'success' => true,
            'restaurant_staff' => $restaurant_staff
        ]);
    }


    public function update(Request $request, $id)
    {
        $restaurant_staff = restaurant_staff::find($id);
        if (!$restaurant_staff) {
            return response()->json([
                'success' => false,
                'message' => 'staff not found'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'user_id' => [
                'required',
                Rule::unique('restaurant_staffs', 'user_id')
                    ->ignore($restaurant_staff->id)
            ],

            'role' => 'required|string|exists:roles,name',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = role::where('name', $request->role)->first();
        $request->merge(['role_id' => $role->id]);

        $data = $request->all();

        $restaurant_staff->update($data);

        return response()->json([
            'success' => true,
            'restaurant_staff' => $restaurant_staff
        ]);
    }


    public function destroy($id)
    {
        $restaurant_staff = restaurant_staff::find($id);

        if (!$restaurant_staff) {
            return response()->json([
                'success' => false,
                'message' => 'staff not found'
            ], 404);
        }
        $restaurant_staff->forceDelete();
        return response()->json([
            'success' => true,
            'message' => 'staff deleted successfully'
        ]);
    }
}
