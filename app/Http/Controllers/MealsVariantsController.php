<?php

namespace App\Http\Controllers;

use App\Models\meals_variants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class MealsVariantsController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $meals_variants = meals_variants::where('brunch_id', $brunch_id)->get();
        return response()->json($meals_variants);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('meals_variants', 'name')],

            'price' => 'required|numeric|min:0',
            'limit' => 'nullable|integer',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $meal_variant = meals_variants::create($request->all());
        return response()->json(['message' => 'Meal variant created successfully', 'data' => $meal_variant], 201);
    }

    public function show($id)
    {
        $meal_variant = meals_variants::find($id);
        if (!$meal_variant) {
            return response()->json(['message' => 'Meal variant not found'], 404);
        }
        return response()->json($meal_variant);
    }

    public function update(Request $request, $id)
    {
        $meal_variant = meals_variants::find($id);
        if (!$meal_variant) {
            return response()->json(['message' => 'Meal variant not found'], 404);
        }

        $validator = Validator::make($request->all(), [

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals_variants', 'name')->ignore($meal_variant->id)
            ],
            'price' => 'required|numeric|min:0',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $meal_variant->update($request->all());
        return response()->json(['message' => 'Meal variant updated successfully', 'data' => $meal_variant], 200);
    }

    public function destroy($id)
    {
        $meal_variant = meals_variants::find($id);
        if (!$meal_variant) {
            return response()->json(['message' => 'Meal variant not found'], 404);
        }

        $meal_variant->delete();
        return response()->json(['message' => 'Meal variant deleted successfully'], 200);
    }
}
