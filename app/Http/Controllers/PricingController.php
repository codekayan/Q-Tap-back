<?php

namespace App\Http\Controllers;

use App\Models\pricing;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class PricingController extends Controller
{
    public function index()
    {
        try {

            $pricings = Pricing::all();
            return response()->json([
                'success' => true,
                'data' => $pricings
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while fetching data' . $e], 500);
        }
    }



    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'orders_limit' => 'required|integer',
                'feature' => 'required|array',
                'monthly_price' => 'required|numeric',
                'yearly_price' => 'required|numeric',
                'is_active' => 'required|in:active,inactive',
            ]);

            $request->merge([
                'feature' => json_encode($request->feature)
            ]);

            $pricing = Pricing::create($request->all());

            return response()->json($pricing, 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while saving data' . $e], 500);
        }
    }



    public function update(Request $request, $id)
    {
        try {

            $pricing = Pricing::find($id);
            if (!$pricing) {
                return response()->json(['error' => 'Pricing not found'], 404);
            }

            $request->validate([
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'orders_limit' => 'nullable|integer',
                'feature' => 'nullable|array',
                'monthly_price' => 'nullable|numeric',
                'yearly_price' => 'nullable|numeric',
                'is_active' => 'nullable|in:active,inactive',
            ]);

            $pricing->update($request->all());

            return response()->json($pricing);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while updating data' . $e], 500);
        }
    }

    public function destroy($id)
    {
        try {

            $pricing = Pricing::find($id);
            if (!$pricing) {
                return response()->json(['error' => 'Pricing not found'], 404);
            }

            $pricing->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while deleting data' . $e], 500);
        }
    }
}
