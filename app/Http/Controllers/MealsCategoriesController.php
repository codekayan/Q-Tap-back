<?php

namespace App\Http\Controllers;

use App\Models\meals_categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class MealsCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $categories = meals_categories::where('brunch_id', $brunch_id)->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals_categories')->where(function ($query) use ($request) {
                    return $query->where('brunch_id', $request->brunch_id);
                }),
            ],

            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');

            $data['image'] = 'storage/' . $path;
        }

        if ($request->hasFile('cover')) {
            $path = $request->file('cover')->store('images', 'public');

            $data['cover'] = 'storage/' . $path;
        }

        $category = meals_categories::create($data);
        return response()->json(['message' => 'Category created successfully', 'data' => $category], 201);
    }

    public function update(Request $request, $id)
    {
        $meals_categories = meals_categories::find($id);

        if (!$meals_categories) {
            return response()->json(['message' => 'Category not found'], 404);
        }



        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals_categories', 'name')->ignore($meals_categories->id)
            ],

            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if ($request->hasFile('image')) {
            if ($meals_categories->image) {
                Storage::disk('public')->delete($meals_categories->image);
            }
            $path = $request->file('image')->store('images', 'public');
            $data['image'] =  'storage/' . $path;
        }

        if ($request->hasFile('cover')) {
            if ($meals_categories->cover) {
                Storage::disk('public')->delete($meals_categories->cover);
            }
            $path = $request->file('cover')->store('images', 'public');
            $data['cover'] =  'storage/' . $path;
        }


        $meals_categories->update($data);
        return response()->json(['message' => 'Category updated successfully', 'data' => $meals_categories], 200);
    }

    public function destroy($id)
    {
        $meals_categories = meals_categories::find($id);

        if (!$meals_categories) {
            return response()->json(['message' => 'Category not found'], 404);
        }


        if ($meals_categories->image) {
            Storage::disk('public')->delete($meals_categories->image);
        }

        $meals_categories->forceDelete();
        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
