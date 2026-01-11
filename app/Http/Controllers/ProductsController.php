<?php

namespace App\Http\Controllers;

use App\Models\products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = products::all();
        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'content' => 'nullable|string|max:255',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }
        $imagePath = '';
        if ($request->hasFile('img')) {
            $image = $request->file('img');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('uploads/products', $imageName, 'public');

            $imagePath = 'storage/' . $imagePath;
        }



        $product = Products::create([
            'name' => $request->input('name'),
            'content' => $request->input('content'),
            'img' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Product stored successfully!',
            'product' => $product,
        ], 201);
    }

    public function update(Request $request, Products $products)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'content' => 'nullable|string|max:255',
            'img' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $products->name = $request->input('name');
        }

        if ($request->hasFile('img')) {
            if ($products->img) {
                $oldImagePath = public_path('storage/' . $products->image);

                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }


            $image = $request->file('img');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('uploads/products', $imageName, 'public');
            $products->img = 'storage/' . $imagePath;
        }

        $products->save();

        return response()->json([
            'message' => 'Product updated successfully!',
            'product' => $products,
        ], 200);
    }

    public function destroy($id)
    {
        $product =  Products::find($id);
        $product->delete();
        return response()->json([
            'message' => 'Product deleted successfully!' ,
            'product_deleted' => $product
        ], 201);
    }
}
