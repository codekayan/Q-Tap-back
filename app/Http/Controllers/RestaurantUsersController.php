<?php

namespace App\Http\Controllers;

use App\Models\restaurant_users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class RestaurantUsersController extends Controller
{

    public function index(Request $request)
    {
        $restaurant_users = restaurant_users::where('brunch_id', $request->brunch_id)->get();
        return response()->json([
            'success' => true,
            'restaurant_users' => $restaurant_users
        ]);
    }



    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:restaurant_users',
            'pin' => 'required|integer|digits_between:1,6|unique:restaurant_users',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $restaurant_users = restaurant_users::create($data);

        return response()->json([
            'success' => true,
            'restaurant_users' => $restaurant_users
        ]);
    }



    public function update(Request $request, $id)
    {

        $restaurant_users = restaurant_users::find($id);
        if (!$restaurant_users) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }


        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('restaurant_users', 'name')->ignore($restaurant_users->id)
            ],

            'pin' => [
                'required',
                Rule::unique('restaurant_users', 'pin')->ignore($restaurant_users->id)
            ],

            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $restaurant_users->update($data);

        return response()->json([
            'success' => true,
            'restaurant_users' => $restaurant_users
        ]);
    }


    public function destroy($id)
    {
        $restaurant_users = restaurant_users::find($id);

        if (!$restaurant_users) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        $restaurant_users->forceDelete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
