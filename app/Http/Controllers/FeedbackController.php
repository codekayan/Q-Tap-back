<?php

namespace App\Http\Controllers;

use App\Models\feedback;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use  Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function index()
    {
        try {

            $feedbacks = Feedback::with('client')->get();

            if ($feedbacks) {

                return response()->json($feedbacks);
            } else {

                return response()->json(['error' => 'Feedbacks not found.'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching feedbacks.'], 500);
        }
    }

    public function store(Request $request)
    {
        try {



            $request['brunch_id'] = auth()->user()->brunch_id;
            $request['client_id'] = auth()->user()->user_id;



            $validator = Validator::make($request->all(), [
                'client_id' => 'required|integer|exists:qtap_clients,id',
                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
                'star' => 'required|integer|min:1|max:5',
                'emoji' => 'required|in:very happy,happy,said',
                'your_goals' => 'required|in:yes,no',
                'missing_Q-tap_Menus' => 'required|string',
                'comment' => 'required|string',
            ]);


            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $feedback = Feedback::create($request->all());

            return response()->json($feedback, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while storing the feedback.' . $e], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {


            $feedback = feedback::find($id);

            if ($feedback) {
                # code...
                $request->validate([
                    'client_id' => 'nullable|integer',
                    'star' => 'nullable|integer|min:1|max:5',
                    'emoji' => 'nullable|in:very happy,happy,said',
                    'your_goals' => 'nullable|in:yes,no',
                    'publish' => 'nullable|in:yes,no',
                    'missing_Q-tap_Menus' => 'nullable|string',
                    'comment' => 'nullable|string',
                ]);

                $feedback->update($request->all());

                return response()->json($feedback);
            } else {
                return response()->json(['error' => 'Feedback not found.'], 404);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Feedback not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while updating the feedback.'], 500);
        }
    }

    public function destroy($id)
    {
        try {


            $feedback = feedback::find($id);


            if ($feedback) {
                $feedback->delete();
                return response()->json(['message' => 'Feedback deleted successfully'], 200);
            } else {

                return response()->json(['error' => 'Feedback not found.'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while deleting the feedback.'], 500);
        }
    }
}
