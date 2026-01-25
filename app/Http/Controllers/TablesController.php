<?php

namespace App\Http\Controllers;

use App\Models\tables;
use App\Models\area;
use App\Models\qtap_clients_brunchs;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class TablesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tables = tables::all();
        return response()->json([
            'success' => true,
            'tables' => $tables
        ]);
    }






    public function store(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
                'area_id' => 'required|integer|exists:areas,id',
                'name' => 'required|string|max:255',
                'size' => 'required|string|max:255',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors()
                ]);
            }


            // إنشاء السجل
            $table = tables::create($request->only(['brunch_id', 'area_id', 'name', 'size']));


            // توليد الرابط وإضافته
            $link = 'https://api.qutap.co' . '/api/menu_by_table/' . $table->id . '/' . $table->brunch_id;

            $table->update(['link' => $link]);

            return response()->json([
                'success' => true,
                'table' => $table
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }




    public function update(Request $request, $id)
    {
        try {

            $tables = tables::find($id);
            if (!$tables) {
                return response()->json([
                    'success' => false,
                    'message' => 'tables not found'
                ]);
            }



            $data = $request->validate([
                'brunch_id' => 'required|integer|max:255',
                'area_id' => 'required|integer|max:255',
                'name' => 'required|string',
                'size' => 'required|string',
                'link' => 'required|string',
            ]);


            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $area_id = area::find($data['area_id']);
            if (!$area_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'area not found'
                ]);
            }



            $tables->update($data);
            return response()->json([
                'success' => true,
                'tables' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $tables = tables::find($id);
        if (!$tables) {
            return response()->json([
                'success' => false,
                'message' => 'tables not found'
            ]);
        }
        $tables->delete();
        return response()->json([
            'success' => true,
            'message' => 'tables deleted successfully'
        ]);
    }



    //---------------------------------------------area----------------------------


    public function get_area()
    {
        $areas = area::all();
        return response()->json([
            'success' => true,
            'areas' => $areas
        ]);
    }

    public function store_area(Request $request)
    {
        try {


            $data = $request->validate([
                'name' => 'required|string',
                'brunch_id' => 'required|integer|max:255',
            ]);

            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $area = area::create($data);
            return response()->json([
                'success' => true,
                'area' => $area
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function update_area(Request $request, $id)
    {
        try {

            $area = area::find($id);
            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'area not found'
                ]);
            }


            $data = $request->validate([
                'name' => 'required|string',
                'brunch_id' => 'required|integer|max:255',
            ]);

            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $area->update($data);
            return response()->json([
                'success' => true,
                'area' => $area
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }



    public function delete_area(Request $request, $id)
    {
        try {

            $area = area::find($id);
            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'area not found'
                ]);
            }


            $area->delete();
            return response()->json([
                'success' => true,
                'message' => 'area deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
