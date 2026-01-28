<?php

namespace App\Http\Controllers;

use App\Models\ClientPricing;
use App\Models\qtap_clients;
use App\Models\qtap_clients_brunchs;
use App\Models\payment_services;
use App\Models\contact_info;
use App\Models\meals;
use App\Models\users_logs;
use App\Models\meals_categories;
use App\Models\orders;
use App\Models\pricing;
use App\Models\Customers_Visits_restaurant;
use App\Models\serving_ways;
use App\Models\workschedule;
use App\Models\revenue_restaurant;
use App\Models\restaurant_user_staff;
use App\Models\tables;
use App\Models\role;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Mail\OTPMail;
use App\Models\coup_plan;

use Illuminate\Support\Facades\Mail;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;




class QtapClientsController extends Controller
{


   /* public function index()
    {
        $qtap_clients = qtap_clients::all();

        $clients_pricing = qtap_clients_brunchs::with('pricing')
            ->get()
            ->groupBy('pricing_id')
            ->map(function ($group) {
                return [
                    'pricing' => $group->first()->pricing->name,
                    'total_clients_brunchs' => $group->count()
                ];
            });



        $allBranchesCount = qtap_clients_brunchs::count();

        $Client = pricing::withCount('qtap_clients_brunchs')->get()->map(function ($package) use ($allBranchesCount) {

            $package->percentage = $allBranchesCount > 0 ? round(($package->qtap_clients_brunchs_count / $allBranchesCount) * 100, 2) . '%' : 0 . '%';

            return $package;
        })->select('id', 'name', 'qtap_clients_brunchs_count', 'percentage');

        $Client['number_branches_clients'] = $allBranchesCount;



        // dd($clients_pricing);

        return response()->json([
            'success' => true,
            'qtap_clients' => $qtap_clients,
            'clients_pricing' => $Client
        ]);
    }*/

    public function index()
    {
        // جلب جميع العملاء مع فروعهم
        $qtap_clients = qtap_clients::with('brunchs')->get();

        // حساب عدد الفروع الكلي باستخدام الموديل
        $allBranchesCount = qtap_clients_brunchs::count();

        $pricingStats = pricing::with(['ClientPricing.client.brunchs'])
            ->get()
            ->map(function ($package) use ($allBranchesCount) {
                $branchesCount = 0;
                foreach ($package->ClientPricing as $clientPricing) {
                    $branchesCount += $clientPricing->client->brunchs->count();
                }

                // حساب النسبة المئوية
                $percentage = $allBranchesCount > 0 ?
                    round(($branchesCount / $allBranchesCount) * 100, 2) . '%' :
                    '0%';

                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'qtap_clients_brunchs_count' => $branchesCount,
                    'percentage' => $percentage
                ];
            });

        // إعداد النتيجة النهائية
        $result = collect($pricingStats);
        $result['number_branches_clients'] = $allBranchesCount;

        return response()->json([
            'success' => true,
            'qtap_clients' => $qtap_clients,
            'clients_pricing' => $result
        ]);
    }

    public function get_info()
    {


        $id = auth()->user()->user_id;

        $qtap_clients = qtap_clients::with([
            'brunchs',
            'brunchs.workschedule',
            'brunchs.contact_info',
            'brunchs.serving_ways',
            'brunchs.payment_services'
        ])->find($id);

        if (!$qtap_clients) {
            return response()->json([
                'error' => 'Client not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'qtap_clients' => $qtap_clients
        ]);
    }


    public function get_client_info($id)
    {

        $qtap_clients = qtap_clients::with([
            'brunchs',
            'brunchs.workschedule',
            'brunchs.contact_info',
            'brunchs.serving_ways',
            'brunchs.payment_services'
        ])->find($id);

        if (!$qtap_clients) {
            return response()->json([
                'error' => 'Client not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'qtap_clients' => $qtap_clients
        ]);
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction(); // ✅ بدء المعاملة

            $last_client = qtap_clients::where('email', $request->email)->first();

            if ($last_client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'البريد الإلكتروني مسجل مسبقاً',
                ], 409); // كود 409 يعني تعارض (Conflict)
            }


            // التحقق من صحة البيانات
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'country' => 'nullable|string|max:255',
                'mobile' => 'required|string|max:255',
                'birth_date' => 'nullable|date',
                'email' => 'required|string|email|max:255|unique:qtap_clients,email',
                'status' => 'nullable|in:active,inactive',
                'password' => 'required|string|min:1',
                'pin' => 'required|string|min:1|max:6',
                'user_type' => 'nullable|in:qtap_clients',
                'pricing_id' => 'required|integer|exists:pricings,id',
                'pricing_way' => 'required|in:monthly_price,yearly_price',
                'affiliate_code' => 'sometimes|string|max:8',
                'contact_info' => 'sometimes|array',
                'coupn_plan_code' => 'sometimes|string|exists:coup_plans,code', // إضافة التحقق من الكوبون
            ]);

            $validatedData['password'] = Hash::make($request->password);
            $pin = $validatedData['pin'];

            // حساب التكلفة بناءً على عدد الفروع
            $branches = collect($request->all())->filter(fn($value, $key) => Str::startsWith($key, 'brunch'));
            $number_of_branches = $branches->count();
            $pricing = pricing::find($request->pricing_id);

            if (!$pricing) {
                return response()->json(['error' => 'Invalid pricing_id.'], 400);
            }

            // حساب السعر الأصلي
            $service_cost = $pricing->{$request['pricing_way']};
            $original_total_cost = match (true) {
                $number_of_branches == 2 => floatval($service_cost * 1.5),
                $number_of_branches == 3 => floatval($service_cost * 2),
                $number_of_branches > 3 => intdiv($number_of_branches, 2) * floatval($service_cost * 1.5) + ($number_of_branches % 2 ? floatval($service_cost * 2) : 0),
                default => $service_cost
            };

            // تطبيق الخصم إذا كان الكوبون صالحًا
            $discount_percentage = 0;
            $discount_amount = 0;
            $coupon_code = null;

            if ($request->filled('coupn_plan_code')) {
                $coupon = coup_plan::where('code', $request->coupn_plan_code)
                                ->where('status', 'active')
                                ->first();

                if ($coupon) {
                    $discount_percentage = $coupon->discount;
                    $discount_amount = $original_total_cost * ($discount_percentage / 100);
                    $coupon_code = $coupon->code;
                }
            }

            $total_cost = $original_total_cost - $discount_amount;
            $total_cost = ceil(max(0, $total_cost)); // التأكد من أن السعر النهائي ليس سالبًا

            if ($request->hasFile('img')) {
                $validatedData['img'] = $request->file('img')->store('uploads/clients', 'public');
                $validatedData['img'] = 'storage/' . $validatedData['img'];
            }

            // ✅ إنشاء العميل
            $new_client = qtap_clients::create([
                'name' => $validatedData['name'],
                'img' => $validatedData['img'] ?? null,
                'country' => $validatedData['country'] ?? null,
                'mobile' => $validatedData['mobile'],
                'birth_date' => $validatedData['birth_date'] ?? null,
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
                'status' => 'inactive',
                'user_type' => $validatedData['user_type'] ?? null,
                'payment_method' => $request['payment_method'] ?? null,
            ]);

            // إنشاء OTP وإرساله
            $otp = 123456;
            $new_client->update(['otp' => $otp]);

            // إرسال البريد الإلكتروني
            //Mail::to($new_client->email)->send(new OTPMail($otp, 'تفعيل حساب Qtap'));

            // Create client pricing record مع تفاصيل الخصم
            ClientPricing::create([
                'client_id' => $new_client->id,
                'pricing_id' => $request->pricing_id,
                'ramin_order' => $pricing->orders_limit,
                'expired_at' => null,
                'payment_methodes' => $request['payment_method'] ?? null,
                'pricing_way' => $request['pricing_way'],
                'original_price' => $service_cost,
                'original_total_price' => $original_total_cost,
                'discount_percentage' => $discount_percentage,
                'discounted_price' => $total_cost,
                'final_price' => $total_cost,
                'coupon_code' => $coupon_code,
                'number_of_branches' => $number_of_branches,
            ]);

            foreach ($branches as $branchData) {
                // ✅ إنشاء الفرع
                $branch = qtap_clients_brunchs::create([
                    'client_id' => $new_client->id,
                    'currency_id' => $branchData['currency_id'] ?? null,
                    'discount_id' => $branchData['discount_id'] ?? null,
                    'business_name' => $branchData['business_name'] ?? null,
                    'business_country' => $branchData['business_country'] ?? null,
                    'business_city' => $branchData['business_city'] ?? null,
                    'latitude' => $branchData['latitude'] ?? null,
                    'longitude' => $branchData['longitude'] ?? null,
                    'business_format' => $branchData['business_format'] ?? null,
                    'menu_design' => $branchData['menu_design'] ?? null,
                    'default_mode' => $branchData['default_mode'] ?? null,
                    'payment_time' => $branchData['payment_time'] ?? null,
                    'call_waiter' => $branchData['call_waiter'] ?? null,
                ]);

                $role_admin = role::create([
                    'name' => 'admin',
                    'menu' => 1,
                    'users' => 1,
                    'orders' => 1,
                    'wallet' => 1,
                    'setting' => 1,
                    'support' => 1,
                    'dashboard' => 1,
                    'customers_log' => 1,
                    'brunch_id' => $branch->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // ✅ إنشاء الموظف الإداري
                restaurant_user_staff::create([
                    'brunch_id' => $branch->id,
                    'email' => $new_client->email,
                    'user_id' => $new_client->id,
                    'password' => $new_client->password,
                    'pin' => $pin,
                    'name' => $new_client->name,
                    'user_type' => $new_client->user_type,
                    'role_id' => $role_admin->id,
                    'role' => $role_admin->name,
                ]);

                if (isset($branchData['workschedules'])) {
                    foreach ($branchData['workschedules'] as $day => $times) {
                        workschedule::create([
                            'brunch_id' => $branch->id,
                            'day' => $day,
                            'opening_time' => $times[0] ?? null,
                            'closing_time' => $times[1] ?? null,
                        ]);
                    }
                }

                // إضافة طرق التقديم
                if (isset($branchData['serving_ways'])) {
                    foreach ($branchData['serving_ways'] as $servingWay) {
                        $data = ['brunch_id' => $branch->id, 'name' => $servingWay];
                        if ($servingWay === 'dine_in') {
                            $data['tables_number'] = $branchData['tables_number'] ?? null;
                        }
                        serving_ways::create($data);
                    }
                }

                // إضافة وسائل الدفع
                if (isset($branchData['payment_services'])) {
                    foreach ($branchData['payment_services'] as $paymentService) {
                        payment_services::create([
                            'brunch_id' => $branch->id,
                            'name' => $paymentService,
                        ]);
                    }
                }

                // ✅ إدخال بيانات الاتصال
                if (isset($branchData['contact_info'])) {
                    contact_info::create([
                        'brunch_id' => $branch->id,
                        'business_phone' => implode(', ', (array) $branchData['contact_info']['business_phone'] ?? []),
                        'business_email' => implode(', ', (array) $branchData['contact_info']['business_email'] ?? []),
                        'website' => implode(', ', (array) $branchData['contact_info']['website'] ?? []),
                        'facebook' => implode(', ', (array) $branchData['contact_info']['facebook'] ?? []),
                        'twitter' => implode(', ', (array) $branchData['contact_info']['twitter'] ?? []),
                        'instagram' => implode(', ', (array) $branchData['contact_info']['instagram'] ?? []),
                        'address' => implode(', ', (array) $branchData['contact_info']['address'] ?? []),
                    ]);
                }
            }

            if ($request['payment_method'] == 'cash' || $total_cost == 0) {
                DB::commit(); // ✅ تأكيد المعاملة
                return response()->json([
                    'status' => 'success',
                    'message' => 'Client and branches added successfully.',
                    'data' => $new_client,
                    'pricing_details' => [
                        'original_price' => $service_cost,
                        'original_total' => $original_total_cost,
                        'discount_percentage' => $discount_percentage,
                        'discount_amount' => $discount_amount,
                        'final_price' => $total_cost,
                        'coupon_code' => $coupon_code,
                    ],
                ], 201);
            }

            $userData = [
                'user_id' => $new_client->id,
                'first_name' => $request->name,
                'last_name' => $request->name,
                'email' => $new_client->email,
                'phone_number' => $request->mobile,
                'affiliate_code' => $request->affiliate_code ?? null
            ];

            $orderData = [
                'total' => $total_cost,
                'currency' => 'EGP',
                'service_name' => 'Qtap Client Registration',
                'items' => [
                    [
                        'name' => 'Qtap Client Registration',
                        "amount_cents" => intval($total_cost) * 100,
                        "description" => "Qtap Client Registration",
                        "quantity" => 1
                    ]
                ],
                'coupon_code' => $coupon_code,
                'discount_applied' => $discount_amount > 0 ? $discount_percentage . '%' : 'No discount',
            ];

            $paymobController = new PaymobController();
            $response = $paymobController->processPayment($orderData, $userData);

            if ($response['status'] == 'success') {
                $payment_url = $response['payment_url'];

                DB::commit(); // ✅ تأكيد المعاملة

                return response()->json([
                    'status' => 'success',
                    'message' => 'Client and branches added successfully.',
                    'payment_url' => $payment_url,
                    'data' => $new_client,
                    'pricing_details' => [
                        'original_price' => $service_cost,
                        'original_total' => $original_total_cost,
                        'discount_percentage' => $discount_percentage,
                        'discount_amount' => $discount_amount,
                        'final_price' => $total_cost,
                        'coupon_code' => $coupon_code,
                    ],
                ], 201);
            } else {
                DB::rollBack(); // ❌ إلغاء جميع العمليات عند حدوث خطاء

                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred while adding data.',
                    'error_details' => $response
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack(); // ❌ إلغاء جميع العمليات عند حدوث خطأ

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while adding data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }

















































































































































































    public function get_brunchs(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validate->errors(),
            ], 422);
        }

        $client = qtap_clients::where('email', $request->email)->first();

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'there is no client with this email',
            ]);
        }

        $brunchs = qtap_clients_brunchs::with('role')->select('id')->where('client_id', $client->id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $brunchs
        ]);
    }

    public function update_profile(Request $request, $id)
    {
        try {

            $brunch_id = $request->brunch_id;

            // التحقق من وجود الفرع
            $brunch = qtap_clients_brunchs::find($brunch_id);
            if (!$brunch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found.',
                ]);
            }

            // التحقق من وجود العميل
            $client = qtap_clients::find($id);
            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found.',
                ]);
            }

            // التحقق من صحة البيانات
            $validatedData = $request->validate([

                'name' => 'sometimes|string|max:255',
                'mobile' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
                'birth_date' => 'sometimes|date',
                'country' => 'sometimes|string|max:255',
                'password' => 'sometimes|string|min:1',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',


                'business_name' => 'sometimes|string|max:255',
                'business_country' => 'sometimes|string|max:255',
                'business_city' => 'sometimes|string|max:255',
                'longitude' => 'sometimes|numeric',
                'latitude' => 'sometimes|numeric',
                'currency_id' => 'sometimes|integer',
                'business_format' => 'sometimes|string|max:255',
                'tables_number' => 'sometimes|integer',


                'contact_info' => 'sometimes|array',
            ]);

            // تشفير كلمة المرور إذا تم تقديمها
            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            // حفظ الصورة إذا تم رفعها
            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients/' . $id, 'public');
                $validatedData['img'] = 'uploads/clients/' . $id . '/' . basename($imagePath);
                $validatedData['img'] = 'storage/' . $validatedData['img'];
            }

            // تحديث بيانات العميل
            $client->update($validatedData);

            // تحديث بيانات الفرع
            $brunch->update($validatedData);

            // تحديث معلومات الاتصال
            if ($request->has('contact_info')) {
                // حذف السجلات القديمة للفرع
                contact_info::where('brunch_id', $brunch_id)->delete();

                // الحصول على معلومات الاتصال من الطلب
                $info = $request->input('contact_info', []);

                if (!empty($info)) {
                    // معالجة القيم لتجنب الأخطاء
                    $contactData = [
                        'brunch_id' => $brunch_id,
                        'business_phone' => is_array($info['business_phone']) ? implode(', ', $info['business_phone']) : $info['business_phone'],
                        'business_email' => is_array($info['business_email']) ? implode(', ', $info['business_email']) : $info['business_email'],
                        'website' => is_array($info['website']) ? implode(', ', $info['website']) : $info['website'],
                        'facebook' => is_array($info['facebook']) ? implode(', ', $info['facebook']) : $info['facebook'],
                        'twitter' => is_array($info['twitter']) ? implode(', ', $info['twitter']) : $info['twitter'],
                        'instagram' => is_array($info['instagram']) ? implode(', ', $info['instagram']) : $info['instagram'],
                        'address' => is_array($info['address']) ? implode(', ', $info['address']) : $info['address'],
                    ];

                    // إنشاء سجل جديد بمعلومات الاتصال
                    $contact = contact_info::create($contactData);

                    // تحقق من نجاح العملية
                    if (!$contact) {
                        // استعادة السجل إذا فشلت عملية الإنشاء
                        contact_info::where('brunch_id', $brunch_id)->onlyTrashed()->restore();
                    } else {
                        // حذف السجلات المحذوفة بشكل دائم إذا نجحت العملية
                        contact_info::where('brunch_id', $brunch_id)->onlyTrashed()->forceDelete();
                    }
                }
            }




            return response()->json([
                'status' => 'success',
                'message' => 'Client and branch updated successfully.',
                'client_data' => $client,
                'contact_business_data' => $contact,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }



    public function update_menu(Request $request, $id)
    {
        try {

            $brunch_id = $request->brunch_id;

            $brunch = qtap_clients_brunchs::find($brunch_id);

            if (!$brunch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brunch not found.',
                ], 404);
            }

            $validatedData = $request->validate([
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'default_mode' => 'sometimes|string|max:255',
                'menu_design' => 'sometimes|string|max:255',
                'tables_number' => 'sometimes|numeric|max:255',
                'serving_ways' => 'sometimes|array',
                'workschedules' => 'sometimes|array',
                'payment_services' => 'sometimes|array',
                'call_waiter' => 'sometimes|in:active,inactive',
                'payment_time' => 'sometimes|in:before,after',
            ]);

            // معالجة الصور
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('uploads/client' . $id . '/brunch' . $brunch_id, 'public');
                $validatedData['logo'] = 'storage/' . $logoPath;
            }

            if ($request->hasFile('cover')) {
                $coverPath = $request->file('cover')->store('uploads/client' . $id . '/brunch' . $brunch_id, 'public');
                $validatedData['cover'] = 'storage/' .  $coverPath;
            }

            // تحديث بيانات الفرع
            $brunch->update($validatedData);

            // تحديث طرق التقديم
            if ($request->has('serving_ways')) {
                $serving_ways = [];
                $created_serving_ways = []; // مصفوفة لحفظ السجلات التي تم إنشاؤها
                serving_ways::where('brunch_id', $brunch_id)->delete();

                foreach ($validatedData['serving_ways'] as $servingWay) {
                    $data = ['brunch_id' => $brunch_id, 'name' => $servingWay];
                    if ($servingWay === 'dine_in') {
                        $data['tables_number'] = $validatedData['tables_number'] ?? null;
                    }

                    // إنشاء السجل وإضافته إلى المصفوفة
                    $created_record = serving_ways::create($data);
                    if ($created_record) {
                        $created_serving_ways[] = $created_record; // إضافة السجل إلى المصفوفة
                    }
                }

                // تحقق من نجاح العملية
                if (empty($created_serving_ways)) {
                    // استعادة السجل إذا فشلت عملية الإنشاء
                    serving_ways::where('brunch_id', $brunch_id)->onlyTrashed()->restore();
                } else {
                    // حذف السجلات المحذوفة بشكل دائم إذا نجحت العملية
                    serving_ways::where('brunch_id', $brunch_id)->onlyTrashed()->forceDelete();
                }
            }

            // تحديث جداول العمل
            if ($request->has('workschedules')) {
                $workschedule = [];
                $created_workschedules = []; // مصفوفة لحفظ السجلات التي تم إنشاؤها

                workschedule::where('brunch_id', $brunch_id)->delete();

                foreach ($validatedData['workschedules'] as $day => $times) {
                    $data = [
                        'brunch_id' => $brunch_id,
                        'day' => $day,
                        'opening_time' => $times[0] ?? null,
                        'closing_time' => $times[1] ?? null,
                    ];

                    // إنشاء السجل وإضافته إلى المصفوفة
                    $created_record = workschedule::create($data);
                    if ($created_record) {
                        $created_workschedules[] = $created_record; // إضافة السجل إلى المصفوفة
                    }
                }

                // تحقق من نجاح العملية
                if (empty($created_workschedules)) {
                    // استعادة السجل إذا فشلت عملية الإنشاء
                    workschedule::where('brunch_id', $brunch_id)->onlyTrashed()->restore();
                } else {
                    // حذف السجلات المحذوفة بشكل دائم إذا نجحت العملية
                    workschedule::where('brunch_id', $brunch_id)->onlyTrashed()->forceDelete();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client and branches updated successfully.',
                'workschedule' => $created_workschedules,
                'serving_ways' => $created_serving_ways,
                'brunch' => $brunch,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }




    public function update_payment(Request $request, $id)
    {
        try {


            $brunch_id = $request->brunch_id;

            $brunch = qtap_clients_brunchs::find($brunch_id);

            if (!$brunch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'brunch not found.',
                ]);
            }




            $client = qtap_clients::find($id);

            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found.',
                ]);
            }


            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'mobile' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
                'birth_date' => 'sometimes|date',
                'country' => 'sometimes|string|max:255',
                'password' => 'sometimes|string|min:1',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',



                'business_name' => 'sometimes|string|max:255',
                'business_phone' => 'sometimes|string|max:255',
                'business_email' => 'sometimes|string|email|max:255',
                'country' => 'sometimes|string|max:255',
                'city' => 'sometimes|string|max:255',
                'longitude' => 'sometimes|decimal|max:255',
                'longitude' => 'sometimes|decimal|max:255',
                'currency_id' => 'sometimes|number|max:255',
                'tables_number' => 'sometimes|number|max:255',



                'business_format' => 'sometimes|string|max:255',
                'menu_design' => 'sometimes|string|max:255',
                'default_mode' => 'sometimes|string|max:255',
                'payment_time' => 'sometimes|string|max:255',
                'call_waiter' => 'sometimes|string|max:255',
                'status' => 'sometimes|in:active,inactive',
            ]);



            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients', 'public');
                $validatedData['img'] = 'storage/' .  $imagePath;
            }

            // تحديث بيانات العميل
            $client->update($validatedData);









            // تحديث الفروع إذا تم إرسالها
            $branches = collect($request->all())->filter(function ($value, $key) {
                return Str::startsWith($key, 'brunch'); // البحث عن المفاتيح التي تبدأ بـ "brunch"
            });

            foreach ($branches as $branchId => $branchData) {
                preg_match('/\d+/', $branchId, $matches);
                $number = $matches[0] ?? null;

                if ($number) {
                    $branch = qtap_clients_brunchs::find($number);

                    if ($branch) {
                        // تحديث بيانات الفرع
                        $branch->update([
                            'currency_id' => $branchData['currency_id'] ?? $branch->currency_id,
                            // 'pricing_id' => $branchData['pricing_id'] ?? $branch->pricing_id,
                            // 'discount_id' => $branchData['discount_id'] ?? $branch->discount_id,
                            'business_name' => $branchData['business_name'] ?? $branch->business_name,
                            'country' => $branchData['country'] ?? $branch->country,
                            'city' => $branchData['city'] ?? $branch->city,
                            'latitude' => $branchData['latitude'] ?? $branch->latitude,
                            'longitude' => $branchData['longitude'] ?? $branch->longitude,
                            'business_format' => $branchData['business_format'] ?? $branch->business_format,
                            'menu_design' => $branchData['menu_design'] ?? $branch->menu_design,
                            'default_mode' => $branchData['default_mode'] ?? $branch->default_mode,
                            'payment_time' => $branchData['payment_time'] ?? $branch->payment_time,
                            'call_waiter' => $branchData['call_waiter'] ?? $branch->call_waiter,
                        ]);


                        // حذف البيانات المرتبطة بالفرع إذا كانت موجودة
                        contact_info::where('brunch_id', $branch->id)->forcedelete();
                        workschedule::where('brunch_id', $branch->id)->forcedelete();
                        serving_ways::where('brunch_id', $branch->id)->forcedelete();
                        payment_services::where('brunch_id', $branch->id)->forcedelete();


                        // إضافة بيانات الاتصال
                        contact_info::create([
                            'brunch_id' => $branch->id,
                            'phone' => $branchData['business_phone'] ?? null,
                            'email' => $branchData['business_email'] ?? null,
                            'website' => $branchData['website'] ?? null,
                            'facebook' => $branchData['facebook'] ?? null,
                            'twitter' => $branchData['twitter'] ?? null,
                            'instagram' => $branchData['instagram'] ?? null,
                            'address' => $branchData['address'] ?? null,
                        ]);

                        // إضافة الجدول الزمني للعمل
                        if (isset($branchData['workschedules'])) {
                            foreach ($branchData['workschedules'] as $day => $times) {
                                workschedule::create([
                                    'brunch_id' => $branch->id,
                                    'day' => $day,
                                    'opening_time' => $times[0] ?? null,
                                    'closing_time' => $times[1] ?? null,
                                ]);
                            }
                        }

                        // إضافة طرق التقديم
                        if (isset($branchData['serving_ways'])) {
                            foreach ($branchData['serving_ways'] as $servingWay) {
                                $data = ['brunch_id' => $branch->id, 'name' => $servingWay];
                                if ($servingWay === 'dine_in') {
                                    $data['tables_number'] = $branchData['tables_number'] ?? null;
                                }
                                serving_ways::create($data);
                            }
                        }

                        // إضافة وسائل الدفع
                        if (isset($branchData['payment_services'])) {
                            foreach ($branchData['payment_services'] as $paymentService) {
                                payment_services::create([
                                    'brunch_id' => $branch->id,
                                    'name' => $paymentService,
                                ]);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client and branches updated successfully.',
                'data' => $client,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }



    public function update_business_info(Request $request, $id)
    {
        try {

            // البحث عن العميل بواسطة المعرف
            $client = qtap_clients::find($id);

            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found.',
                ]);
            }

            // التحقق من البيانات المرسلة فقط
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'country' => 'sometimes|string|max:255',
                'mobile' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date',
                'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
                'status' => 'sometimes|in:active,inactive',
                'password' => 'sometimes|string|min:1',
                'user_type' => 'sometimes|in:qtap_clients',
            ]);

            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients', 'public');
                $validatedData['img'] =  'storage/' . $imagePath;
            }

            // تحديث بيانات العميل
            $client->update($validatedData);

            // تحديث الفروع إذا تم إرسالها
            $branches = collect($request->all())->filter(function ($value, $key) {
                return Str::startsWith($key, 'brunch'); // البحث عن المفاتيح التي تبدأ بـ "brunch"
            });

            foreach ($branches as $branchId => $branchData) {
                preg_match('/\d+/', $branchId, $matches);
                $number = $matches[0] ?? null;

                if ($number) {
                    $branch = qtap_clients_brunchs::find($number);

                    if ($branch) {
                        // تحديث بيانات الفرع
                        $branch->update([
                            'currency_id' => $branchData['currency_id'] ?? $branch->currency_id,
                            // 'pricing_id' => $branchData['pricing_id'] ?? $branch->pricing_id,
                            // 'discount_id' => $branchData['discount_id'] ?? $branch->discount_id,
                            'business_name' => $branchData['business_name'] ?? $branch->business_name,
                            'country' => $branchData['country'] ?? $branch->country,
                            'city' => $branchData['city'] ?? $branch->city,
                            'latitude' => $branchData['latitude'] ?? $branch->latitude,
                            'longitude' => $branchData['longitude'] ?? $branch->longitude,
                            'business_format' => $branchData['business_format'] ?? $branch->business_format,
                            'menu_design' => $branchData['menu_design'] ?? $branch->menu_design,
                            'default_mode' => $branchData['default_mode'] ?? $branch->default_mode,
                            'payment_time' => $branchData['payment_time'] ?? $branch->payment_time,
                            'call_waiter' => $branchData['call_waiter'] ?? $branch->call_waiter,
                        ]);


                        // حذف البيانات المرتبطة بالفرع إذا كانت موجودة
                        contact_info::where('brunch_id', $branch->id)->forcedelete();
                        workschedule::where('brunch_id', $branch->id)->forcedelete();
                        serving_ways::where('brunch_id', $branch->id)->forcedelete();
                        payment_services::where('brunch_id', $branch->id)->forcedelete();


                        // إضافة بيانات الاتصال
                        contact_info::create([
                            'brunch_id' => $branch->id,
                            'phone' => $branchData['business_phone'] ?? null,
                            'email' => $branchData['business_email'] ?? null,
                            'website' => $branchData['website'] ?? null,
                            'facebook' => $branchData['facebook'] ?? null,
                            'twitter' => $branchData['twitter'] ?? null,
                            'instagram' => $branchData['instagram'] ?? null,
                            'address' => $branchData['address'] ?? null,
                        ]);

                        // إضافة الجدول الزمني للعمل
                        if (isset($branchData['workschedules'])) {
                            foreach ($branchData['workschedules'] as $day => $times) {
                                workschedule::create([
                                    'brunch_id' => $branch->id,
                                    'day' => $day,
                                    'opening_time' => $times[0] ?? null,
                                    'closing_time' => $times[1] ?? null,
                                ]);
                            }
                        }

                        // إضافة طرق التقديم
                        if (isset($branchData['serving_ways'])) {
                            foreach ($branchData['serving_ways'] as $servingWay) {
                                $data = ['brunch_id' => $branch->id, 'name' => $servingWay];
                                if ($servingWay === 'dine_in') {
                                    $data['tables_number'] = $branchData['tables_number'] ?? null;
                                }
                                serving_ways::create($data);
                            }
                        }

                        // إضافة وسائل الدفع
                        if (isset($branchData['payment_services'])) {
                            foreach ($branchData['payment_services'] as $paymentService) {
                                payment_services::create([
                                    'brunch_id' => $branch->id,
                                    'name' => $paymentService,
                                ]);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client and branches updated successfully.',
                'data' => $client,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data is incomplete or incorrect.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating data.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }


    // public function update(Request $request, $id)
    // {
    //     try {
    //         // البحث عن العميل بواسطة المعرف
    //         $client = qtap_clients::find($id);

    //         if (!$client) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Client not found.',
    //             ]);
    //         }

    //         // التحقق من البيانات المرسلة فقط
    //         $validatedData = $request->validate([
    //             'name' => 'sometimes|string|max:255',
    //             'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //             'country' => 'sometimes|string|max:255',
    //             'mobile' => 'sometimes|string|max:255',
    //             'birth_date' => 'sometimes|date',
    //             'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
    //             'status' => 'sometimes|in:active,inactive',
    //             'password' => 'sometimes|string|min:1',
    //             'user_type' => 'sometimes|in:qtap_clients',
    //         ]);

    //         if ($request->has('password')) {
    //             $validatedData['password'] = Hash::make($request->password);
    //         }

    //         if ($request->hasFile('img')) {
    //             $imagePath = $request->file('img')->store('uploads/clients', 'public');
    //             $validatedData['img'] =  'storage/' . $imagePath;
    //         }

    //         // تحديث بيانات العميل
    //         $client->update($validatedData);

    //         // تحديث الفروع إذا تم إرسالها
    //         $branches = collect($request->all())->filter(function ($value, $key) {
    //             return Str::startsWith($key, 'brunch'); // البحث عن المفاتيح التي تبدأ بـ "brunch"
    //         });

    //         foreach ($branches as $branchId => $branchData) {
    //             preg_match('/\d+/', $branchId, $matches);
    //             $number = $matches[0] ?? null;

    //             if ($number) {
    //                 $branch = qtap_clients_brunchs::find($number);

    //                 if ($branch) {
    //                     // تحديث بيانات الفرع
    //                     $branch->update([
    //                         'currency_id' => $branchData['currency_id'] ?? $branch->currency_id,
    //                         // 'pricing_id' => $branchData['pricing_id'] ?? $branch->pricing_id,
    //                         // 'discount_id' => $branchData['discount_id'] ?? $branch->discount_id,
    //                         'business_name' => $branchData['business_name'] ?? $branch->business_name,
    //                         'country' => $branchData['country'] ?? $branch->country,
    //                         'city' => $branchData['city'] ?? $branch->city,
    //                         'latitude' => $branchData['latitude'] ?? $branch->latitude,
    //                         'longitude' => $branchData['longitude'] ?? $branch->longitude,
    //                         'business_format' => $branchData['business_format'] ?? $branch->business_format,
    //                         'menu_design' => $branchData['menu_design'] ?? $branch->menu_design,
    //                         'default_mode' => $branchData['default_mode'] ?? $branch->default_mode,
    //                         'payment_time' => $branchData['payment_time'] ?? $branch->payment_time,
    //                         'call_waiter' => $branchData['call_waiter'] ?? $branch->call_waiter,
    //                     ]);


    //                     // حذف البيانات المرتبطة بالفرع إذا كانت موجودة
    //                     contact_info::where('brunch_id', $branch->id)->forcedelete();
    //                     workschedule::where('brunch_id', $branch->id)->forcedelete();
    //                     serving_ways::where('brunch_id', $branch->id)->forcedelete();
    //                     payment_services::where('brunch_id', $branch->id)->forcedelete();


    //                     // إضافة بيانات الاتصال
    //                     contact_info::create([
    //                         'brunch_id' => $branch->id,
    //                         'phone' => $branchData['business_phone'] ?? null,
    //                         'email' => $branchData['business_email'] ?? null,
    //                         'website' => $branchData['website'] ?? null,
    //                         'facebook' => $branchData['facebook'] ?? null,
    //                         'twitter' => $branchData['twitter'] ?? null,
    //                         'instagram' => $branchData['instagram'] ?? null,
    //                         'address' => $branchData['address'] ?? null,
    //                     ]);

    //                     // إضافة الجدول الزمني للعمل
    //                     if (isset($branchData['workschedules'])) {
    //                         foreach ($branchData['workschedules'] as $day => $times) {
    //                             workschedule::create([
    //                                 'brunch_id' => $branch->id,
    //                                 'day' => $day,
    //                                 'opening_time' => $times[0] ?? null,
    //                                 'closing_time' => $times[1] ?? null,
    //                             ]);
    //                         }
    //                     }

    //                     // إضافة طرق التقديم
    //                     if (isset($branchData['serving_ways'])) {
    //                         foreach ($branchData['serving_ways'] as $servingWay) {
    //                             $data = ['brunch_id' => $branch->id, 'name' => $servingWay];
    //                             if ($servingWay === 'dine_in') {
    //                                 $data['tables_number'] = $branchData['tables_number'] ?? null;
    //                             }
    //                             serving_ways::create($data);
    //                         }
    //                     }

    //                     // إضافة وسائل الدفع
    //                     if (isset($branchData['payment_services'])) {
    //                         foreach ($branchData['payment_services'] as $paymentService) {
    //                             payment_services::create([
    //                                 'brunch_id' => $branch->id,
    //                                 'name' => $paymentService,
    //                             ]);
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Client and branches updated successfully.',
    //             'data' => $client,
    //         ], 200);
    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Some data is incomplete or incorrect.',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An error occurred while updating data.',
    //             'error_details' => $e->getMessage(),
    //         ], 500);
    //     }
    // }




    public function Performance_restaurant($startYear, $endYear)
    {
        // تحديد بداية ونهاية السنة الأولى (startYear)
        $startDate1 = $startYear . '-01-01';
        $endDate1 = $startYear . '-12-31';

        // تحديد بداية ونهاية السنة الثانية (endYear)
        $startDate2 = $endYear . '-01-01';
        $endDate2 = $endYear . '-12-31';

        // جلب الاشتراكات للسنة الأولى
        $Subscriptions1 = orders::where('status', 'confirmed')
            ->whereBetween('created_at', [$startDate1, $endDate1])
            ->count();

        // جلب الطلبات للسنة الأولى
        $Orders1 =orders::where('status', 'confirmed')
            ->whereBetween('created_at', [$startDate1, $endDate1])
            ->count();

        // جلب الاشتراكات للسنة الثانية
        $Subscriptions2 = orders::where('status', 'confirmed')
            ->whereBetween('created_at', [$startDate2, $endDate2])
            ->count();

        // جلب الطلبات للسنة الثانية
        $Orders2 = orders::where('status', 'confirmed')
            ->whereBetween('created_at', [$startDate2, $endDate2])
            ->count();

        return response()->json([
            'success' => true,
            'Subscriptions_' . $startYear => $Subscriptions1,
            'Orders_' . $startYear => $Orders1,
            'Subscriptions_' . $endYear => $Subscriptions2,
            'Orders_' . $endYear => $Orders2,
        ]);
    }



    public function update(Request $request, $id)
    {
        try {
            $client = qtap_clients::find($id);

            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found.',
                ], 404);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'country' => 'sometimes|string|max:255',
                'mobile' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date',
                'email' => 'sometimes|string|email|max:255|unique:qtap_clients,email,' . $id,
                'status' => 'sometimes|in:active,inactive',
                'password' => 'sometimes|string|min:1',
                'user_type' => 'sometimes|in:qtap_clients',
                'order_id' => 'sometimes|string',
            ]);

            if ($request->has('password')) {
                $validatedData['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('uploads/clients', 'public');
                $validatedData['img'] = 'storage/' . $imagePath;
            }

            $client->update($validatedData);

            // تحديث الفروع
            if ($request->has('brunchs')) {
                foreach ($request->brunchs as $brunchData) {
                    $branch = qtap_clients_brunchs::find($brunchData['id'] ?? null);

                    if ($branch && $branch->client_id == $client->id) {
                        $branch->update([
                            'currency_id' => $brunchData['currency_id'] ?? $branch->currency_id,
                          //  'pricing_id' => $brunchData['pricing_id'] ?? $branch->pricing_id,
                            'discount_id' => $brunchData['discount_id'] ?? $branch->discount_id,
                            'payment_method' => $brunchData['payment_method'] ?? $branch->payment_method,
                            'business_name' => $brunchData['business_name'] ?? $branch->business_name,
                            'business_country' => $brunchData['business_country'] ?? $branch->business_country,
                            'business_city' => $brunchData['business_city'] ?? $branch->business_city,
                            'latitude' => $brunchData['latitude'] ?? $branch->latitude,
                            'longitude' => $brunchData['longitude'] ?? $branch->longitude,
                            'business_format' => $brunchData['business_format'] ?? $branch->business_format,
                            'menu_design' => $brunchData['menu_design'] ?? $branch->menu_design,
                            'default_mode' => $brunchData['default_mode'] ?? $branch->default_mode,
                            'payment_time' => $brunchData['payment_time'] ?? $branch->payment_time,
                            'call_waiter' => $brunchData['call_waiter'] ?? $branch->call_waiter,
                            'status' => $brunchData['status'] ?? $branch->status,
                            'order_id' => $brunchData['order_id'] ?? $branch->order_id,
                            'affiliate_code' => $brunchData['affiliate_code'] ?? $branch->affiliate_code,
                        ]);

                        // تحديث مواعيد العمل
                        if (isset($brunchData['workschedule'])) {
                            workschedule::where('brunch_id', $branch->id)->forceDelete();
                            foreach ($brunchData['workschedule'] as $schedule) {
                                workschedule::create([
                                    'brunch_id' => $branch->id,
                                    'day' => $schedule['day'],
                                    'opening_time' => $schedule['opening_time'],
                                    'closing_time' => $schedule['closing_time'],
                                ]);
                            }
                        }

                        // تحديث بيانات الاتصال
                        if (isset($brunchData['contact_info'])) {
                            contact_info::where('brunch_id', $branch->id)->forceDelete();
                            foreach ($brunchData['contact_info'] as $contact) {
                                contact_info::create([
                                    'brunch_id' => $branch->id,
                                    'business_email' => $contact['business_email'],
                                    'business_phone' => $contact['business_phone'],
                                    'website' => $contact['website'],
                                    'facebook' => $contact['facebook'],
                                    'twitter' => $contact['twitter'],
                                    'instagram' => $contact['instagram'],
                                    'address' => $contact['address'],
                                ]);
                            }
                        }

                        // تحديث طرق الخدمة
                        if (isset($brunchData['serving_ways'])) {
                            serving_ways::where('brunch_id', $branch->id)->forceDelete();
                            foreach ($brunchData['serving_ways'] as $way) {
                                serving_ways::create([
                                    'brunch_id' => $branch->id,
                                    'name' => $way['name'],
                                    'tables_number' => $way['tables_number'] ?? null,
                                ]);
                            }
                        }


                        // تحديث طرق الخدمة
                        if (isset($brunchData['payment_services'])) {
                            payment_services::where('brunch_id', $branch->id)->forceDelete();
                            foreach ($brunchData['payment_services'] as $way) {
                                payment_services::create([
                                    'brunch_id' => $branch->id,
                                    'name' => $way['name']
                                ]);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Client updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($id)
    {
        $qtap_clients = qtap_clients::find($id);

        if (!$qtap_clients) {
            return response()->json([
                'status' => 'error',
                'message' => 'Client not found',
            ], 404);
        }

        $qtap_clients->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Client deleted successfully.',
        ], 200);
    }

    public function menu($id)
    {


        $menu = meals_categories::with(

            'meals',
            'meals.variants',
            'meals.extras',
            'meals.meals_special_offer',
            //'meals.discounts'
        )->where('brunch_id', $id)->get();

        $customers__visits = Customers_Visits_restaurant::where('brunch_id', $id)->first();

        if ($customers__visits) {


            $customers__visits->update([
                'visits' => $customers__visits->visits + 1
            ]);
        } else {

            Customers_Visits_restaurant::create([
                'brunch_id' => $id,
                'visits' => 1,
            ]);
        }




        return response()->json([
            'status' => 'success',
            'data' => $menu
        ]);
    }

   public function menu_all_restaurants()
{
    $menu = qtap_clients::with([
            'brunchs' => function($query) {
                $query->where('status', 'active');
            },
            'brunchs.cat_meal',
            'brunchs.cat_meal.meals',
            'brunchs.cat_meal.meals.variants',
            'brunchs.serving_ways',
            'brunchs.cat_meal.meals.extras',
            'brunchs.cat_meal.meals.meals_special_offer',
           // 'brunchs.cat_meal.meals.discounts'
        ])
        ->where('status', 'active') // هذه هي السطر المهم الذي يضمن تصفية العملاء النشطين فقط
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $menu
    ]);
}

    public function menu_by_table($tableId, $brunchId)
    {
        $menu = meals_categories::with(
            'meals',
            'meals.variants',
            'meals.extras',
            'meals.meals_special_offer',
          //  'meals.discounts'
        )->where('brunch_id', $brunchId)->get();

        $customersVisit = Customers_Visits_restaurant::where('brunch_id', $brunchId)->first();

        if ($customersVisit) {
            $customersVisit->update([
                'visits' => $customersVisit->visits + 1
            ]);
        } else {
            Customers_Visits_restaurant::create([
                'brunch_id' => $brunchId,
                'visits' => 1,
            ]);
        }

        $table = tables::with('area_info')->where('id', $tableId)->where('brunch_id', $brunchId)->first();

        return response()->json([
            'status' => 'success',
            'data' => $menu,
            'table' => $table
        ]);
    }




    public function dashboard($id)
    {

        //-----------------------users login chart----------------------------------------------


        $users_logs = users_logs::with('user')->where('brunch_id', $id)->get();

        //---------------Total Orders chart------------------------------------------------------

        $Customers_Visits_restaurant = Customers_Visits_restaurant::where('brunch_id', $id)->first();
        $visit_count = $Customers_Visits_restaurant ? $Customers_Visits_restaurant->visits : 0;

        $orders = orders::where('brunch_id', $id)->count();

        if ($visit_count > 0) {
            $order_percentage = ($orders / $visit_count) * 100;
            $visit_only_percentage = 100 - $order_percentage;
        } else {
            $order_percentage = 0;
            $visit_only_percentage = 0;
        }




        //---------------Total Orders chart------------------------------------------------------


        $branchesPerMonth = orders::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(id) as total_branches')
            ->where('brunch_id', $id)
            ->whereYear('created_at', date('Y')) // استعلام فقط للسنة الحالية
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month'); // استخدام keyBy لتحديد الشهر كمفتاح للمصفوفة

        // إنشاء مصفوفة تحتوي على جميع الأشهر
        $allMonths = collect([
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec'
        ]);

        // دمج الأشهر مع عدد الأفرع
        $branchesPerMonthWithAllMonths = $allMonths->map(function ($monthName, $monthNumber) use ($branchesPerMonth) {
            $branchData = $branchesPerMonth->get($monthNumber);

            return [
                'month_name' => $monthName,
                'total_order' => $branchData ? $branchData->total_branches : 0
            ];
        });

        return response()->json([
            'status' => 'success',
            'order_percentage' => $order_percentage . '%',
            'visit_only_percentage' => $visit_only_percentage . '%',
            'orders' => $orders,
            'Customers_Visits_count' => $visit_count,
            'data' => $branchesPerMonthWithAllMonths,
            'total_orders' => $branchesPerMonthWithAllMonths->sum('total_order'),
            'users_logs' => $users_logs
        ]);
    }


    public function Sales_by_days_restaurant($days)
    {
        // تحقق من أن الأيام المدخلة صحيحة
        if (!is_numeric($days) || $days <= 0) {
            return response()->json(['error' => 'عدد الأيام غير صحيح.']);
        }

        // استعلام للحصول على عدد الأفرع وقيمة الأرباح لكل يوم في السنة الحالية
        $revenuePerDay = Orders::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, DAY(created_at) as day, SUM(total_price) as total_revenue, MIN(created_at) as created_at')
        ->whereYear('created_at', date('Y')) // استخدام السنة الحالية
            ->groupBy('year', 'month', 'day')
            ->orderBy('created_at', 'asc') // ترتيب البيانات حسب التاريخ
            ->get();





        // تحويل البيانات إلى مصفوفة تحتوي على تاريخ اليوم وقيمة الربح
        $revenueData = $revenuePerDay->map(function ($item) {
            return [
                'date' => $item->year . '-' . sprintf('%02d', $item->month) . '-' . sprintf('%02d', $item->day),
                'total_revenue' => $item->total_revenue,
            ];
        });

        // حساب عدد الأسابيع (تقريبًا) بناءً على الأيام المدخلة
        $totalWeeks = ceil($days / 7); // حساب الأسابيع بشكل تقريبي
        $remainingDays = $days % 7; // الأيام المتبقية

        // تقسيم الأيام إلى أسابيع بناءً على الأيام المدخلة
        $weeks = [];
        $currentWeek = 1;
        $currentWeekRevenue = 0;
        $currentDays = 0;

        // إنشاء جميع الأسابيع المطلوبة
        for ($week = 1; $week <= $totalWeeks; $week++) {
            $currentWeekRevenue = 0;
            $currentWeekDays = 0;

            // إضافة الأيام لهذا الأسبوع
            while ($currentDays < $days && $currentWeekDays < 7) {
                if (isset($revenueData[$currentDays])) {
                    $currentWeekRevenue += $revenueData[$currentDays]['total_revenue'] ?? 0;
                }
                $currentDays++;
                $currentWeekDays++;
            }

            // إضافة الأسبوع إلى المصفوفة
            $weeks[] = [
                'week' => 'الأسبوع ' . $week,
                'start_date' => ($currentWeekDays > 0 && isset($revenueData[$currentDays - $currentWeekDays])) ? $revenueData[$currentDays - $currentWeekDays]['date'] : 'غير متوفر',
                'end_date' => ($currentWeekDays > 0 && isset($revenueData[$currentDays - 1])) ? $revenueData[$currentDays - 1]['date'] : 'غير متوفر',
                'total_revenue' => $currentWeekRevenue
            ];
        }

        return response()->json([
            'status' => 'success',
            'weeks' => $weeks,
            'total_revenue' => array_sum(array_column($weeks, 'total_revenue')),
        ]);
    }



    public function Sales_restaurant($year)
    {
        // تحقق من أن السنة المدخلة صحيحة
        if (!is_numeric($year) || $year < 1900 || $year > date('Y')) {
            return response()->json(['error' => 'سنة غير صحيحة.']);
        }

        // استعلام للحصول على عدد الأفرع وقيمة الأرباح لكل شهر في السنة المحددة
        $branchesPerMonth = orders::  // تأكد من أن العلاقة بين "qtap_clients_brunchs" و "revenue" مهيأة
            selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_price) as total_revenue') // جمع الأرباح
            ->whereYear('created_at', $year) // استخدام السنة المدخلة
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month'); // استخدام keyBy لتحديد الشهر كمفتاح للمصفوفة

        // إنشاء مصفوفة تحتوي على جميع الأشهر
        $allMonths = collect([
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec'
        ]);

        // دمج الأشهر مع عدد الأفرع والأرباح
        $branchesPerMonthWithAllMonths = $allMonths->map(function ($monthName, $monthNumber) use ($branchesPerMonth) {
            $branchData = $branchesPerMonth->get($monthNumber);

            return [
                'month_name' => $monthName,
                'total_revenue' => $branchData ? $branchData->total_revenue : 0
            ];
        });

        return response()->json($branchesPerMonthWithAllMonths);
    }


    public function wallet_restaurant($id, $year)
    {

        $Revenue = revenue_restaurant::whereYear('created_at', $year)->get();

        $Revenue = $Revenue->sum('revenue');

        return response()->json([
            "success" => true,
            "Revenue" => $Revenue,
            "balance" => $Revenue - 0,
            "Withdrawal" => 0,

        ]);
    }


    //-----------------------users login chart----------------------------------------------


    public function Customer_log($id, $year1, $year2)
    {
        $users_logs = orders::where('brunch_id', $id)->whereBetween('created_at', [$year1, $year2])->get();

        return response()->json([
            'status' => 'success',
            'users_logs' => $users_logs
        ]);
    }
}
