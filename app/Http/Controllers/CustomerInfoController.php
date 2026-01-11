<?php

namespace App\Http\Controllers;

use App\Models\customer_info;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CustomerInfoController extends Controller
{
    public function index()
    {
        try {
            $customer_info = customer_info::all();
            return response()->json([
                'success' => true,
                'customer_info' => $customer_info
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customer info.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:15',
                'address' => 'required|string|max:255',
            ]);


            $customer_data = customer_info::where('phone', $validatedData['phone'])->first();

            if ($customer_data) {
                return response()->json([
                    'message' => 'Phone number already exists',
                    'data' => $customer_data
                ], 409);
            } else {
                $new_customer_data = customer_info::create($validatedData);

                return response()->json([
                    'message' => 'Data stored successfully',
                    'data' => $new_customer_data
                ], 201);
            }
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, customer_info $customer_info)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'phone' => 'sometimes|string|max:15',
                'address' => 'sometimes|string|max:255',
            ]);

            $customer_info->update($validatedData);

            return response()->json([
                'message' => 'Data updated successfully',
                'data' => $customer_info
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred during update.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(customer_info $customer_info)
    {
        try {
            $customer_info->delete();

            return response()->json([
                'message' => 'Data deleted successfully',
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred during deletion.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
