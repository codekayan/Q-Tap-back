<?php

namespace App\Http\Controllers;


use App\Models\orders;
use App\Models\qtap_clients_brunchs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\tables;
use App\Models\meals;
use App\Models\note;

use App\Events\notify_msg;
use App\Models\meals_discount;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{

    public function index($brunch_id)
    {

        $brunch = qtap_clients_brunchs::where('status', 'active')->find($brunch_id);

        if (!$brunch) {
            return response()->json([
                'message' => 'Brunch not active',
            ]);
        }

        $orders = orders::with(
            [
                'orders_processing',
                'orders_processing.user',
            ]


        )->where('brunch_id', $brunch_id)->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }


   /* public function store(Request $request)
    {
        DB::beginTransaction();





        try {
            // التحقق من البيانات العامة
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'phone' => 'required|max:255',
                'comments' => 'nullable|max:2000',

                'city' => 'nullable|max:255',
                'address' => 'nullable|max:255',
                'type' => 'required|in:dinein,takeaway,delivery',

                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',

                'table_id' => 'nullable|integer|exists:tables,id',
                'tax' => 'required|numeric',
                'total_price' => 'required|numeric',

                'payment_way' => 'required|in:cash,wallet',
                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',

                'meals' => 'required|array|min:1',
                'meals.*.meal_id' => 'required|integer|exists:meals,id',
                'meals.*.quantity' => 'required|integer|min:1',
                'meals.*.discount_code' => 'nullable|string',
                'meals.*.variants' => 'nullable|array',
                'meals.*.variants.*' => 'integer|exists:meals_variants,id',
                'meals.*.extras' => 'nullable|array',
                'meals.*.extras.*' => 'integer|exists:meals_extras,id',
                'meals.*.size' => 'nullable|string|in:s,m,l',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // التحقق من الفرع
            $brunch = qtap_clients_brunchs::with('client')->where('status', 'active')->find($request->brunch_id);
            if (!$brunch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brunch not active.',
                ]);
            }


            // حساب عدد الطلبات لهذا الفرع خلال هذا الشهر
            // $currentMonthOrdersCount = orders::where('brunch_id', $brunch->id)
            //     ->whereYear('created_at', now()->year)
            //     ->whereMonth('created_at', now()->month)
            //     ->count();



            // $limit = $brunch->pricing->limit;


            // if ($currentMonthOrdersCount >= $limit) {

            //     $notify = note::create([
            //         'title' => 'Order limit reached',
            //         'content' => 'You have reached the limit of orders for this month.',
            //     ]);

            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'resturant reached the limit of orders ',
            //     ]);
            // }

            // إنشاء الطلب الرئيسي
            $order = orders::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'comments' => $request->comments,
                'type' => $request->type,
                'tax' => $request->tax,
                'total_price' => $request->total_price,
                'payment_way' => $request->payment_way,
                'brunch_id' => $request->brunch_id,
                'city' => $request->city,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'table_id' => $request->table_id,


                // البيانات المستخرجة من أول عنصر في مصفوفة meals
                'meal_id' => json_encode(collect($request->meals)->pluck('meal_id')),
                'quantity' => json_encode(collect($request->meals)->pluck('quantity')),
                'discount_code' => json_encode(collect($request->meals)->pluck('discount_code')),
                'size' => json_encode(collect($request->meals)->pluck('size')),
                'variants' => json_encode(collect($request->meals)->pluck('variants')),
                'extras' => json_encode(collect($request->meals)->pluck('extras')),

            ]);




            // معالجة الدفع إن كان wallet
            if ($request->payment_way === 'wallet') {

                $total_price = ceil($request->total_price);
                $firstMeal = meals::find($request->meals[0]['meal_id']); // أول وجبة لعرض الاسم

                $userData = [
                    'first_name' => $request->name,
                    'last_name' => $request->name,
                    'email' => $brunch->client->email,
                    'phone_number' => $request->phone,
                    'order_id' => $order->id
                ];

                $orderData = [
                    'total' => $total_price,
                    'currency' => 'EGP',
                    'service_name' => $firstMeal ? $firstMeal->name : 'Order',
                    'items' => [
                        [
                            'name' => $firstMeal ? $firstMeal->name : 'Meal',
                            'amount_cents' => intval($total_price) * 100,
                            'description' => 'Qtap Order',
                            'quantity' => 1
                        ]
                    ]
                ];

                $paymobController = new PaymobController();
                $response = $paymobController->processPayment_orders($orderData, $userData);

                if ($response['status'] == 'success') {
                    DB::commit();

                    $type = 'add_order';

                    event(new notify_msg($order, $type));

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Order created successfully',
                        'order' => $order,
                        'payment_url' => $response['payment_url']
                    ], 201);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'response' => $response
                    ], 500);
                }
            } else {
                DB::commit();

                $type = 'add_order';
                event(new notify_msg($order, $type));

                return response()->json([
                    'success' => true,
                    'order' => $order
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
*/



public function store(Request $request)
{
    DB::beginTransaction();

    try {
        // التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'phone' => 'required|max:255',
            'comments' => 'nullable|max:2000',
            'city' => 'nullable|max:255',
            'address' => 'nullable|max:255',
            'type' => 'required|in:dinein,takeaway,delivery',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'table_id' => 'nullable|integer|exists:tables,id',
            'tax' => 'required|numeric',
            'payment_way' => 'required|in:cash,wallet',
            'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
            'discount_code' => 'nullable|string',
            'meals' => 'required|array|min:1',
            'meals.*.meal_id' => 'required|integer|exists:meals,id',
            'meals.*.quantity' => 'required|integer|min:1',
            'meals.*.variants' => 'nullable|array',
            'meals.*.variants.*' => 'integer|exists:meals_variants,id',
            'meals.*.extras' => 'nullable|array',
            'meals.*.extras.*' => 'integer|exists:meals_extras,id',
            'meals.*.size' => 'nullable|string|in:s,m,l',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // التحقق من أن البرانش نشط
        $brunch = qtap_clients_brunchs::with('client')->where('status', 'active')->find($request->brunch_id);
        if (!$brunch) {
            return response()->json([
                'status' => 'error',
                'message' => 'Brunch not active.',
            ], 400);
        }

        // حساب السعر الإجمالي
        $subTotal = 0;
        $mealsData = [];

        foreach ($request->meals as $mealItem) {
            $meal = meals::findOrFail($mealItem['meal_id']);

            // تحديد السعر حسب الحجم
            $price = $meal->price;
            if (isset($mealItem['size'])) {
                if ($mealItem['size'] === 's' && $meal->price_small) {
                    $price = $meal->price_small;
                } elseif ($mealItem['size'] === 'm' && $meal->price_medium) {
                    $price = $meal->price_medium;
                } elseif ($mealItem['size'] === 'l' && $meal->price_large) {
                    $price = $meal->price_large;
                }
            }

            // تطبيق خصم الوجبة إذا كان موجوداً
            if ($meal->discount_precentage) {
                $price = $price * (1 - ($meal->discount_precentage / 100));
            }

            $mealTotal = $price * $mealItem['quantity'];
            $subTotal += $mealTotal;

            $mealsData[] = $mealItem;
        }

        // تطبيق خصم الطلب إذا وجد
        $discountAmount = 0;
        if ($request->discount_code) {
            $discount = meals_discount::where('code', $request->discount_code)
                ->where('brunch_id', $request->brunch_id)
                ->where('status', 'active')
                ->first();

            if ($discount) {
                $discountAmount = $subTotal * ($discount->discount / 100);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or inactive discount code for this brunch.',
                ], 400);
            }
        }

        // حساب الضريبة والسعر النهائي
        $taxAmount = ($subTotal - $discountAmount) * ($request->tax / 100);
        $totalPrice = ($subTotal - $discountAmount) + $taxAmount;

        // إنشاء الطلب
        $order = orders::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'comments' => $request->comments,
            'type' => $request->type,
            'tax' => $request->tax,
            'total_price' => $totalPrice,
            'payment_way' => $request->payment_way,
            'brunch_id' => $request->brunch_id,
            'city' => $request->city,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'table_id' => $request->table_id,
            'subtotal' => $subTotal, // أضف هذا السطر
            'discount_code' => $request->discount_code,
            'meal_id' => json_encode(collect($request->meals)->pluck('meal_id')),
            'quantity' => json_encode(collect($request->meals)->pluck('quantity')),
            'size' => json_encode(collect($request->meals)->pluck('size')),
            'variants' => json_encode(collect($request->meals)->pluck('variants')),
            'extras' => json_encode(collect($request->meals)->pluck('extras')),
        ]);

        // معالجة الدفع إذا كانت الطريقة wallet
        if ($request->payment_way === 'wallet') {
            $total_price = ceil($totalPrice);
            $firstMeal = meals::find($request->meals[0]['meal_id']);

            $userData = [
                'first_name' => $request->name,
                'last_name' => $request->name,
                'email' => $brunch->client->email,
                'phone_number' => $request->phone,
                'order_id' => $order->id
            ];

            $orderData = [
                'total' => $total_price,
                'currency' => 'EGP',
                'service_name' => $firstMeal ? $firstMeal->name : 'Order',
                'items' => [
                    [
                        'name' => $firstMeal ? $firstMeal->name : 'Meal',
                        'amount_cents' => intval($total_price) * 100,
                        'description' => 'Qtap Order',
                        'quantity' => 1
                    ]
                ]
            ];

          //  $paymobController = new PaymobController();
         //   $paymentResponse = $paymobController->processPayment_orders($orderData, $userData);

            // تحويل JsonResponse إلى array
          //  $responseData = json_decode($paymentResponse->getContent(), true);

          /*  if ($responseData['status'] == 'success') {
                DB::commit();

                event(new notify_msg($order, 'add_order'));

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order created successfully',
                    'order' => $order,
                    'payment_url' => $responseData['payment_url']
                ], 201);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'response' => $responseData
                ], 500);
            }*/
        }

        DB::commit();

      //  event(new notify_msg($order, 'add_order'));

        return response()->json([
            'success' => true,
            'order' => $order
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong.',
            'error' => $e->getMessage()
        ], 500);
    }
}



public function checkDiscountStatus(Request $request, $brunchId)
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

    // التحقق من أن brunchId صحيح
    if (!is_numeric($brunchId)) {
        return response()->json([
            'status' => 'error',
            'message' => 'معرّف الفرع غير صحيح'
        ], 400);
    }

    // البحث عن كود الخصم
    $discount = meals_discount::where('code', $request->code)
                ->where('brunch_id', $brunchId)
                ->where('status', 'active')
                ->first();


    // إرجاع النتيجة
    return response()->json([
        'status' => 'success',
        'message' => $discount ? 'كود الخصم فعال' : 'كود الخصم غير فعال',
        'discount' => $discount
    ]);
}





































































































    public function cancel_order(Request $request, $id)
    {
        $order = orders::with(['orders_processing', 'orders_processing.user'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }


        if ($order->status == 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Order already cancelled'
            ]);
        }



        $order->update([
            'status' => 'cancelled',
        ]);


        $type = 'cancel_order';






        event(new notify_msg($order, $type));



        return response()->json([
            'success' => true,
            'order' => $order,
            'message' => 'Order cancelled successfully'
        ]);
    }



    public function destroy($id)
    {
        $orders = orders::find($id);

        if (!$orders) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }

        $orders->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }
}
