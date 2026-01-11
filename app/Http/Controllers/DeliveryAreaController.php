<?php

namespace App\Http\Controllers;

use App\Models\delivery_area;
use App\Models\qtap_clients_brunchs;
use Illuminate\Http\Request;

class DeliveryAreaController extends Controller
{

    public function index()
    {
        $delivery_areas = delivery_area::all();
        return response()->json([
            'success' => true,
            'delivery_areas' => $delivery_areas
        ]);
    }


    public function store(Request $request)
    {
        try {


            $data = $request->validate([
                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
                'country' => 'required|string',
                'city' => 'required|string',
                'phone' => 'required|string',
                'cost' => 'required|numeric',
            ]);


            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $delivery_area = delivery_area::create($request->all());
            return response()->json([
                'success' => true,
                'delivery_area' => $delivery_area
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

            $delivery_area = delivery_area::find($id);
            if (!$delivery_area) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_area not found'
                ]);
            }
            $data = $request->validate([
                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
                 'country' => 'required|string',
                'city' => 'required|string',
                'phone' => 'required|string',
                'cost' => 'required|numeric',
            ]);


            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $delivery_area->update($request->all());
            return response()->json([
                'success' => true,
                'delivery_area' => $delivery_area
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
        try {

            $delivery_area = delivery_area::find($id);
            if (!$delivery_area) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_area not found'
                ]);
            }
            $delivery_area->delete();
            return response()->json([
                'success' => true,
                'message' => 'delivery_area deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
