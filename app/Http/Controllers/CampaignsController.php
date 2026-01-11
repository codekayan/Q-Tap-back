<?php

namespace App\Http\Controllers;

use App\Models\Campaigns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignsController extends Controller
{
    public function index()
    {
        $campaigns = Campaigns::all();
        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'commission' => 'required|numeric',
            'limit' => 'required|numeric',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $campaign = Campaigns::create([
            'name' => $request->name,
            'commission' => $request->commission,
            'limit' => $request->limit,
            'status' => $request->status,
        ]);

        return response()->json($campaign, 201);
    }

    public function update(Request $request, $id)
    {
        $campaign = Campaigns::find($id); // البحث عن الحملة باستخدام الـ ID

        if (!$campaign) {
            return response()->json([
                'status' => 'error',
                'message' => 'Campaign not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'commission' => 'numeric',
            'limit' => 'numeric',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $campaign->update($request->only(['name', 'commission', 'limit', 'status']));

        return response()->json($campaign);
    }

    public function destroy($id)
    {
        $campaign = Campaigns::find($id); // البحث عن الحملة باستخدام الـ ID

        if (!$campaign) {
            return response()->json([
                'status' => 'error',
                'message' => 'Campaign not found',
            ], 404);
        }

        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted successfully.']);
    }
}
