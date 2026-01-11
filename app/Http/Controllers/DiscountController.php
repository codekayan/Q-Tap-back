<?php

namespace App\Http\Controllers;

use App\Models\discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{


    public function index()
    {
        $discounts = discount::all();
        return response()->json([
            'success' => true,
            'discounts' => $discounts
        ]);
    }


    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'discount' => 'required|numeric',
                'status' => 'required|in:active,inactive',
            ]);

            $discount = discount::create($request->all());

            return response()->json([
                'success' => true,
                'discount' => $discount
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ]);
        }
    }


    public function update(Request $request, $id)
    {
       try {

            $discount = discount::find($id);

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'discount not found'
                ]);
            }

            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'discount' => 'required|numeric',
                'status' => 'required|in:active,inactive',
            ]);

            $discount->update($request->all());

            return response()->json([
                'success' => true,
                'discount' => $discount
            ]);

       } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ]);
       }

    }


    public function destroy($id)
    {
        $discount = discount::find($id);
        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => 'discount not found'
            ]);
        }
        $discount->delete();
        return response()->json([
            'success' => true,
            'message' => 'discount deleted successfully'
        ]);
    }
}
