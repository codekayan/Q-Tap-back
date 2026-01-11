<?php

namespace App\Http\Controllers;

use App\Models\currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class currencyController extends Controller
{
    public function index()
    {
        $currencies = currency::all();
        return response()->json([
            'success' => true,
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'symbol' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency = currency::create([
            'name' => $request->name,
            'symbol' => $request->symbol,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'currency created successfully',
            'data' => $currency,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $currency = currency::find($id);

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'currency not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'symbol' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currency->update($request->only(['name', 'symbol']));

        return response()->json([
            'status' => 'success',
            'message' => 'currency updated successfully',
            'data' => $currency,
        ], 200);
    }

    public function destroy($id)
    {
        $currency = currency::find($id);

        if (!$currency) {
            return response()->json([
                'status' => 'error',
                'message' => 'currency not found',
            ], 404);
        }

        $currency->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'currency deleted successfully',
        ], 200);
    }
}
