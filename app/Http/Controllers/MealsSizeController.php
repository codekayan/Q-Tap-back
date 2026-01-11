<?php

namespace App\Http\Controllers;

use App\Models\meals_size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MealsSizeController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $meals_sizes = meals_size::where('brunch_id', $brunch_id)->get();
        return response()->json($meals_sizes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'size' => 'required|string|in:s,m,l',
            'price' => 'required|numeric',
            'meals_id' => 'required|integer|exists:meals,id',  // Assuming 'meals' table exists
                        'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id', // Assuming 'brunches' table exists
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $meals_size = meals_size::create($request->all());
        return response()->json(['message' => 'Meal size created successfully', 'data' => $meals_size], 201);
    }

    public function show($id)
    {
        $meals_size = meals_size::find($id);
        if (!$meals_size) {
            return response()->json(['message' => 'Meal size not found'], 404);
        }
        return response()->json($meals_size);
    }

    public function update(Request $request, $id)
    {
        $meals_size = meals_size::find($id);
        if (!$meals_size) {
            return response()->json(['message' => 'Meal size not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'size' => 'required|string|in:s,m,l',
            'price' => 'required|numeric',
            'meals_id' => 'required|integer|exists:meals,id',
              'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $meals_size->update($request->all());
        return response()->json(['message' => 'Meal size updated successfully', 'data' => $meals_size], 200);
    }

    public function destroy($id)
    {
        $meals_size = meals_size::find($id);
        if (!$meals_size) {
            return response()->json(['message' => 'Meal size not found'], 404);
        }

        $meals_size->delete();
        return response()->json(['message' => 'Meal size deleted successfully'], 200);
    }
}
