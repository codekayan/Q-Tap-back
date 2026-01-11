<?php

namespace App\Http\Controllers;

use App\Models\feedback_restaurant;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class FeedbackRestaurantController extends Controller
{
    public function index()
    {


            $feedbacks = feedback_restaurant::with('brunch')->get();

            return response()->json($feedbacks);


    }

    public function store(Request $request)
    {
        try {





            $validator = Validator::make($request->all(), [

                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
                'star' => 'required|integer|min:1|max:5',
                'emoji' => 'required|in:very happy,happy,said',
                'your_goals' => 'required|in:yes,no',
                'phone' => 'required|string',
                'missing_Q-tap_Menus' => 'required|string',
                'comment' => 'required|string',
            ]);


            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $feedback_restaurant = feedback_restaurant::create($request->all());

            return response()->json($feedback_restaurant, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while storing the feedback_restaurant.' . $e], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {


            $feedback_restaurant = feedback_restaurant::find($id);

            if ($feedback_restaurant) {
                # code...
                $request->validate([
                    'star' => 'nullable|integer|min:1|max:5',
                    'emoji' => 'nullable|in:very happy,happy,said',
                    'your_goals' => 'nullable|in:yes,no',
                    'publish' => 'nullable|in:yes,no',
                    'phone' => 'nullable|string',
                    'missing_Q-tap_Menus' => 'nullable|string',
                    'comment' => 'nullable|string',
                ]);

                $feedback_restaurant->update($request->all());

                return response()->json($feedback_restaurant);
            } else {
                return response()->json(['error' => 'feedback_restaurant not found.'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while updating the feedback_restaurant.'], 500);
        }
    }

    public function destroy($id)
    {
        try {


            $feedback_restaurant = feedback_restaurant::find($id);


            if ($feedback_restaurant) {
                $feedback_restaurant->delete();
                return response()->json(['message' => 'feedback_restaurant deleted successfully'], 200);
            } else {

                return response()->json(['error' => 'feedback_restaurant not found.'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while deleting the feedback_restaurant.'], 500);
        }
    }
}
