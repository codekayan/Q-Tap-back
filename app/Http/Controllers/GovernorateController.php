<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Http\Request;

class GovernorateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getGovernorates(Request $request)
    {
        $governorates = Governorate::select('id', 'name_ar', 'name_en', 'code')
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $governorates
        ]);
    }

    /**
     * الحصول على مدن محافظة محددة
     */
    public function getCitiesByGovernorate(Request $request, $governorateId)
    {
        $cities = City::where('governorate_id', $governorateId)
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Governorate $governorate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Governorate $governorate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Governorate $governorate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Governorate $governorate)
    {
        //
    }
}
