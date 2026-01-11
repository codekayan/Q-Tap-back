<?php

namespace App\Http\Controllers;

use App\Models\meals_discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\qtap_clients;
use App\Models\qtap_clients_brunchs;
use Illuminate\Support\Facades\Auth;

class MealsDiscountController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $discounts = meals_discount::where('brunch_id', $brunch_id)->get();
        return response()->json(['discounts' => $discounts]);
    }

    /*public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:255', Rule::unique('meals_discounts', 'code')],
            'discount' => 'required|numeric|min:0|max:100',
            'status' => 'nullable|in:active,inactive',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $discount = meals_discount::create($request->all());
        return response()->json(['message' => 'Discount created successfully', 'data' => $discount], 201);
    }*/

    public function store(Request $request)
    {
        // جلب المستخدم المصادق عليه من الجارد المخصص
        $staffUser = Auth::guard('restaurant_user_staff')->user();

        if (!$staffUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Please login first'
            ], 401);
        }

        // البحث عن العميل المرتبط بهذا الموظف
        $client = qtap_clients::where('email', $staffUser->email)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client account not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:255', Rule::unique('meals_discounts', 'code')],
            'discount' => 'required|numeric|min:0|max:100',
            'status' => 'nullable|in:active,inactive',
            'brunch_id' => [
                'required',
                'integer',
                // التحقق من أن البرانش موجود وينتمي للعميل
                function ($attribute, $value, $fail) use ($client) {
                    $brunchExists = qtap_clients_brunchs::where('id', $value)
                        ->where('client_id', $client->id)
                        ->exists();

                    if (!$brunchExists) {
                        $fail('The selected branch does not belong to your restaurant.');
                    }
                }
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // إضافة client_id تلقائياً إلى البيانات قبل الحفظ
        $data = $request->all();
        $data['client_id'] = $client->id;

        $discount = meals_discount::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Discount created successfully',
            'data' => $discount
        ], 201);
    }


    public function update(Request $request, $id)
    {
        // استرجاع كائن الخصم باستخدام id
        $meals_discount = meals_discount::find($id);

        if (!$meals_discount) {
            return response()->json(['message' => 'Discount not found'], 404);
        }


        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals_discounts', 'code')->ignore($meals_discount->id)
            ],
            'discount' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $meals_discount->update($request->all());
        return response()->json(['message' => 'Discount updated successfully', 'data' => $meals_discount], 200);
    }

    public function destroy($id)
    {
        $meals_discount = meals_discount::find($id);

        if (!$meals_discount) {
            return response()->json(['message' => 'Discount not found'], 404);
        }


        $meals_discount->delete();
        return response()->json(['message' => 'Discount deleted successfully'], 200);
    }
}
