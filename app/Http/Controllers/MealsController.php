<?php

namespace App\Http\Controllers;

use App\Models\meals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use App\Models\meals_variants;
use App\Models\meals_extra;

class MealsController extends Controller
{
    public function index(Request $request)
    {
        $brunch_id = $request->input('brunch_id');
        $meals = meals::with('discounts', 'variants', 'extras')->where('brunch_id', $brunch_id)->get();
        return response()->json($meals);
    }

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => [
    //             'required',
    //             'string',
    //             'max:255',
    //             Rule::unique('meals', 'name')->where(function ($query) use ($request) {
    //                 return $query->where('brunch_id', $request->brunch_id);
    //             }),
    //         ],
    //         'price_small' => 'nullable|numeric',
    //         'price_medium' => 'nullable|numeric',
    //         'price_large' => 'nullable|numeric',
    //         'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'Brief' => 'nullable|string',
    //         'Description' => 'nullable|string',
    //         'Ingredients' => 'nullable|string',
    //         'Calories' => 'nullable|integer',
    //         'Time' => 'nullable|string',
    //         'Tax' => 'nullable|numeric',
    //         'price' => 'required|numeric',
    //         'discount_id' => 'nullable|integer',
    //         'categories_id' => 'required|integer',
    //          'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $data = $request->all();

    //     if ($request->hasFile('img')) {
    //         $path = $request->file('img')->store('images', 'public');
    //         $data['img'] = 'storage/' . $path;
    //     }

    //     $meal = meals::create($data);



    //     $variants = meals_variants::create([

    //     ]);


    //     $extras = meals_extra::create([

    //     ]);






    //     return response()->json(['message' => 'Meal extra created successfully', 'data' => $meal], 201);
    // }





    public function store(Request $request)
    {
        // التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals', 'name')->where(function ($query) use ($request) {
                    return $query->where('brunch_id', $request->brunch_id);
                }),
            ],
            'price_small' => 'nullable|numeric',
            'price_medium' => 'nullable|numeric',
            'price_large' => 'nullable|numeric',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'Brief' => 'nullable|string',
            'Description' => 'nullable|string',
            'Ingredients' => 'nullable|string',
            'Calories' => 'nullable|integer',
            'Time' => 'nullable|string',
            'Tax' => 'nullable|numeric',
            'price' => 'required|numeric',
            'discount_precentage' => 'nullable|integer|min:0|max:100',
            'limit_variants' => 'nullable|integer',
            'categories_id' => 'required|integer',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',

            // التحقق من صحة `variants` و `extra`
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.price' => 'required|numeric',

            'extra' => 'nullable|array',
            'extra.*.name' => 'required|string|max:255',
            'extra.*.price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // تشغيل المعاملة لضمان التراجع في حالة حدوث أي خطأ
        DB::beginTransaction();

        try {
            // استخراج البيانات المطلوبة
            $data = $request->only([
                'name',
                'Brief',
                'Description',
                'Ingredients',
                'Calories',
                'Time',
                'Tax',
                'price',
                'discount_precentage',
                'categories_id',
                'brunch_id',
                'price_small',
                'price_medium',
                'price_large'
            ]);

            // حفظ الصورة إن وجدت
            if ($request->hasFile('img')) {
                $path = $request->file('img')->store('images', 'public');
                $data['img'] = 'storage/' . $path;
            }

            // إنشاء الوجبة
            $meal = meals::create($data);

            // حفظ `variants` إذا وُجدت
            if ($request->has('variants')) {
                foreach ($request->variants as $variant) {
                    meals_variants::create([
                        'meals_id' => $meal->id,
                        'name' => $variant['name'],
                        'price' => $variant['price'],
                        'brunch_id' => $data['brunch_id'],
                    ]);
                }
            }

            // حفظ `extra` إذا وُجدت
            if ($request->has('extra')) {
                foreach ($request->extra as $extra) {
                    meals_extra::create([
                        'meals_id' => $meal->id,
                        'name' => $extra['name'],
                        'price' => $extra['price'],
                        'brunch_id' => $data['brunch_id'],
                    ]);
                }
            }

            // تأكيد التغييرات وحفظها في قاعدة البيانات
            DB::commit();

            return response()->json([
                'message' => 'Meal created successfully',
                'data' => $meal
            ], 201);
        } catch (\Exception $e) {
            // التراجع عن جميع العمليات في حالة حدوث خطأ
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create meal',
                'error' => $e->getMessage()
            ], 500);
        }
    }




































    public function show($id)
    {
        $meal = meals::find($id);
        if (!$meal) {
            return response()->json(['message' => 'Meal not found'], 404);
        }
        return response()->json($meal);
    }

    public function update(Request $request, $id)
    {
        $meal = meals::find($id);
        if (!$meal) {
            return response()->json(['message' => 'Meal not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meals', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('brunch_id', $request->brunch_id);
                    })
                    ->ignore($meal->id),
            ],
            'price_small' => 'nullable|numeric',
            'price_medium' => 'nullable|numeric',
            'price_large' => 'nullable|numeric',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'Brief' => 'nullable|string',
            'Description' => 'nullable|string',
            'Ingredients' => 'nullable|string',
            'Calories' => 'nullable|integer',
            'Time' => 'nullable|string',
            'Tax' => 'nullable|numeric',
            'price' => 'required|numeric',
            'discount_id' => 'nullable|integer',
            'limit_variants' => 'nullable|integer',
            'categories_id' => 'required|integer',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',

            // التحقق من variants و extra عند التعديل
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.price' => 'required|numeric',

            'extra' => 'nullable|array',
            'extra.*.name' => 'required|string|max:255',
            'extra.*.price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $data = $request->only([
                'name',
                'Brief',
                'Description',
                'Ingredients',
                'Calories',
                'Time',
                'Tax',
                'price',
                'discount_id',
                'categories_id',
                'brunch_id',
                'price_small',
                'price_medium',
                'price_large'
            ]);

            if ($request->hasFile('img')) {
                if ($meal->img && Storage::disk('public')->exists(str_replace('storage/', '', $meal->img))) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $meal->img));
                }
                $path = $request->file('img')->store('images', 'public');
                $data['img'] = 'storage/' . $path;
            }

            $meal->update($data);

            // حذف القديم من variants و extra ثم إعادة الإضافة
            meals_variants::where('meals_id', $meal->id)->delete();
            meals_extra::where('meals_id', $meal->id)->delete();

            if ($request->has('variants')) {
                foreach ($request->variants as $variant) {
                    meals_variants::create([
                        'meals_id' => $meal->id,
                        'name' => $variant['name'],
                        'price' => $variant['price'],
                        'brunch_id' => $data['brunch_id'],
                    ]);
                }
            }

            if ($request->has('extra')) {
                foreach ($request->extra as $extra) {
                    meals_extra::create([
                        'meals_id' => $meal->id,
                        'name' => $extra['name'],
                        'price' => $extra['price'],
                        'brunch_id' => $data['brunch_id'],
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Meal updated successfully', 'data' => $meal], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update meal',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        $meal = meals::find($id);
        if (!$meal) {
            return response()->json(['message' => 'Meal not found'], 404);
        }

        if ($meal->img) {
            Storage::disk('public')->delete($meal->img);
        }

        $meal->forceDelete();
        return response()->json(['message' => 'Meal deleted successfully'], 200);
    }
}
