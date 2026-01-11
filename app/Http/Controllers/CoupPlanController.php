<?php

namespace App\Http\Controllers;

use App\Models\coup_plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoupPlanController extends Controller
{

public function index(Request $request)
{
    try {
        // بناء الاستعلام الأساسي
        $query = coup_plan::query();

        // تطبيق الفلتر حسب حالة الكوبون إذا تم إرسالها
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // تطبيق الفلتر حسب كود الكوبون إذا تم إرسالها
        if ($request->has('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        // تطبيق الفلتر حسب نسبة الخصم إذا تم إرسالها
        if ($request->has('discount')) {
            $query->where('discount', $request->discount);
        }

        // ترتيب النتائج حسب الأحدث (حسب created_at تنازلياً)
        $query->orderBy('created_at', 'desc');

        // جلب النتائج
        $coupons = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $coupons
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'فشل في استرجاع البيانات',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * إنشاء سجل جديد في coup_plan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:coup_plans,code|max:50',
            'discount' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive'
        ], [
            'code.required' => 'كود الخصم مطلوب',
            'code.unique' => 'كود الخصم مستخدم مسبقاً',
            'discount.required' => 'نسبة الخصم مطلوبة',
            'discount.numeric' => 'نسبة الخصم يجب أن تكون رقماً',
            'status.required' => 'حالة الخصم مطلوبة'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $coupon = coup_plan::create([
                'code' => $request->code,
                'discount' => $request->discount,
                'status' => $request->status
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء كوبون الخصم بنجاح',
                'data' => $coupon
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في إنشاء كوبون الخصم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض سجل محدد بواسطة الـ ID
     */
    public function show($id)
    {
        try {
            $coupon = coup_plan::find($id);

            if (!$coupon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'كوبون الخصم غير موجود'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $coupon
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في استرجاع البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث سجل موجود
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|unique:coup_plans,code,'.$id.'|max:50',
            'discount' => 'sometimes|numeric|min:0|max:100',
            'status' => 'sometimes|in:active,inactive'
        ], [
            'code.unique' => 'كود الخصم مستخدم مسبقاً',
            'discount.numeric' => 'نسبة الخصم يجب أن تكون رقماً'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $coupon = coup_plan::find($id);

            if (!$coupon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'كوبون الخصم غير موجود'
                ], 404);
            }

            $coupon->update($request->only(['code', 'discount', 'status']));

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث كوبون الخصم بنجاح',
                'data' => $coupon
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في تحديث البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف سجل محدد
     */
    public function destroy($id)
    {
        try {
            $coupon = coup_plan::find($id);

            if (!$coupon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'كوبون الخصم غير موجود'
                ], 404);
            }

            $coupon->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف كوبون الخصم بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل في حذف البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }






    public function checkDiscountStatus(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
        ], [
            'code.required' => 'يجب إدخال كود الخصم',
            'code.string' => 'كود الخصم يجب أن يكون نصاً',
            'code.max' => 'كود الخصم يجب ألا يتجاوز 50 حرفاً',
        ]);

        // في حالة وجود أخطاء في التحقق
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في التحقق من الصحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // البحث عن كود الخصم
        $discount = coup_plan::where('code', $request->code)
                    ->where('status', 'active')
                    ->first();


        // إرجاع النتيجة
        return response()->json([
            'status' => 'success',
            'message' => $discount ? 'كود الخصم فعال' : 'كود الخصم غير فعال',
            'discount' => $discount
        ]);
    }

}
