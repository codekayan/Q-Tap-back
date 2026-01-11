<?php

namespace App\Http\Controllers;

use App\Models\meals_extra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MealsExtraController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $meals_extra = meals_extra::where('brunch_id', $brunch_id)->get();
        return response()->json($meals_extra);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:meals_extras',
            'price' => 'required|numeric|min:0',
            'variants_id' => 'required|integer|exists:meals_variants,id',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $meal_extra = meals_extra::create($request->all());
        return response()->json(['message' => 'Meal extra created successfully', 'data' => $meal_extra], 201);
    }

    public function show($id)
    {
        $meal_extra = meals_extra::find($id);
        if (!$meal_extra) {
            return response()->json(['message' => 'Meal extra not found'], 404);
        }
        return response()->json($meal_extra);
    }

    public function update(Request $request, $id)
    {
        $meal_extra = meals_extra::find($id);
        if (!$meal_extra) {
            return response()->json(['message' => 'Meal extra not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:meals_extras,name,' . $meal_extra->id,
            'price' => 'required|numeric|min:0',
            'variants_id' => 'required|integer|exists:meals_variants,id',
                        'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $meal_extra->update($request->all());
        return response()->json(['message' => 'Meal extra updated successfully', 'data' => $meal_extra], 200);
    }

    public function destroy($id)
    {
        $meal_extra = meals_extra::find($id);
        if (!$meal_extra) {
            return response()->json(['message' => 'Meal extra not found'], 404);
        }

        $meal_extra->delete();
        return response()->json(['message' => 'Meal extra deleted successfully'], 200);
    }
}
