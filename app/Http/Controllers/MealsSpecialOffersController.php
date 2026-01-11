<?php

namespace App\Http\Controllers;

use App\Models\meals_special_offers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


// call Rule
use Illuminate\Validation\Rule;


class MealsSpecialOffersController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $special_offers = meals_special_offers::where('brunch_id', $brunch_id)->get();
        return response()->json($special_offers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:meals_special_offers,name,NULL,id,brunch_id,' . $request->brunch_id,
            'description' => 'required|string',
            'discount' => 'required|string|min:0|max:100',
            'before_discount' => 'required|numeric',
            'after_discount' => 'required|numeric',
            'meals_id' => 'required|integer|exists:meals,id',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $data = $request->all();

        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('images', 'public');
            $data['img'] = 'storage/' . $path;
        }

        $special_offer = meals_special_offers::create($data);
        return response()->json(['message' => 'Special offer created successfully', 'data' => $special_offer], 201);
    }

    public function show($id)
    {
        $special_offer = meals_special_offers::find($id);
        if (!$special_offer) {
            return response()->json(['message' => 'Special offer not found'], 404);
        }
        return response()->json($special_offer);
    }

    public function update(Request $request, $id)
    {
        $special_offer = meals_special_offers::find($id);
        if (!$special_offer) {
            return response()->json(['message' => 'Special offer not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'discount' => 'required|string|min:0|max:100',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
            'before_discount' => 'required|numeric',
            'after_discount' => 'required|numeric',
            'meals_id' => 'required|integer|exists:meals,id',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
            'description' => 'required|string',

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals_special_offers', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('brunch_id', $request->brunch_id);
                    })
                    ->ignore($id), // تجاهل السجل الحالي عند التعديل
            ],
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if ($request->hasFile('img')) {
            if ($special_offer->img) {
                Storage::disk('public')->delete($special_offer->img);
            }
            $path = $request->file('img')->store('images', 'public');
            $data['img'] = 'storage/' . $path;
        }

        $special_offer->update($data);
        return response()->json(['message' => 'Special offer updated successfully', 'data' => $special_offer], 200);
    }

    public function destroy($id)
    {
        $special_offer = meals_special_offers::find($id);
        if (!$special_offer) {
            return response()->json(['message' => 'Special offer not found'], 404);
        }

        $special_offer->delete();
        return response()->json(['message' => 'Special offer deleted successfully'], 200);
    }
}
