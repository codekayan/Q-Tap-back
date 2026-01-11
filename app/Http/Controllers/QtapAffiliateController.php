<?php

namespace App\Http\Controllers;

use App\Models\qtap_affiliate;
use App\Models\affiliate_payment_info;
use App\Models\affiliate_Revenues;
use App\Models\affiliate_transactions;
use App\Models\affiliate_clicks;
use App\Models\qtap_clients_brunchs;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Hash;


class QtapAffiliateController extends Controller
{

    public function index()
    {
        $affiliates_active = qtap_affiliate::where('status', 'active')->get();
        $affiliates_inactive = qtap_affiliate::where('status', 'inactive')->get();

        return response()->json([
            'success' => true,
            'affiliates_active' => $affiliates_active,
            'affiliates_inactive' => $affiliates_inactive
        ]);
    }



    private function generateAffiliateCode($length = 8)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }



    public function get_myinfo()
    {
        $user = auth()->user();
        $affiliate = qtap_affiliate::with('payment_info')->withCount('clicks_count')->find($user->id);

        return response()->json([
            'success' => true,
            'affiliate' => $affiliate
        ]);
    }

    public function store(Request $request)
    {
        try {

            qtap_affiliate::where('email', $request->email)->where('status', 'inactive')->delete();

            $affiliate_code = $this->generateAffiliateCode();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'mobile' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:qtap_affiliates,email',
                'password' => 'required|string|min:1',
                'country' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date',
                'user_type' => 'nullable|in:qtap_affiliates',
                'payment_way' => 'nullable|in:bank_account,wallet,credit_card',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }


            $validatedData = $request->all();


            $link =  $affiliate_code;


            $validatedData['password'] = Hash::make($validatedData['password']);

            $validatedData['code'] = $link;


            if ($request->hasFile('img')) {
                // store
                $path = $request->file('img')->store('images', 'public');
                $validatedData['img'] = 'storage/' . $path;
            }

            $affiliate = qtap_affiliate::create($validatedData);


            $paymentInfo_data = $request->all();

            $paymentInfo_data['affiliate_id'] = $affiliate->id;

            if ($request['payment_way']) {

                $paymentInfo = affiliate_payment_info::create($paymentInfo_data);
            }





            return response()->json(['status' => 'success', 'message' => 'تمت الإضافة بنجاح.', 'data' => compact('affiliate')], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'بيانات غير صحيحة.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'حدث خطأ.', 'error' => $e->getMessage()], 500);
        }
    }



    public function affiliate_transactions($id)
    {

        $transactions = affiliate_transactions::with('affiliate')->where('created_at', '>=', now()->subDays(30))->where('affiliate_id', $id)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }


    public function affiliate_transactions_all()
    {

        $transactions = affiliate_transactions::with('affiliate')->where('created_at', '>=', now()->subDays(30))->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }




    public function affiliate_Revenues($year)
    {
        // عدد المستخدمين حسب السنة
        $users = affiliate_Revenues::whereYear('created_at', $year)->count();

        // الإيرادات حسب الأشهر
        $monthlyRevenues = affiliate_Revenues::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(value_order) as total_revenue')
            ->whereYear('created_at', $year)
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        // عدد المستخدمين حسب الأشهر
        $monthlyUsers = qtap_clients_brunchs::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(id) as users_count')
            ->whereYear('created_at', $year)
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        // أسماء الأشهر
        $allMonths = collect([
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر'
        ]);

        // الإيرادات لكل شهر
        $revenuePerMonth = $allMonths->map(function ($monthName, $monthNumber) use ($monthlyRevenues) {
            $data = $monthlyRevenues->get($monthNumber);
            return [
                'month_name' => $monthName,
                'total_revenue' => $data ? $data->total_revenue : 0
            ];
        });

        // عدد المستخدمين لكل شهر
        $usersPerMonth = $allMonths->map(function ($monthName, $monthNumber) use ($monthlyUsers) {
            $data = $monthlyUsers->get($monthNumber);
            return [
                'month_name' => $monthName,
                'users_count' => $data ? $data->users_count : 0
            ];
        });

        // مجموع الإيرادات السنوية
        $totalYearlyRevenue = $revenuePerMonth->sum('total_revenue');

        return response()->json([
            'success' => true,
            'users' => $users,
            'users_count_by_year' => $usersPerMonth,
            'branchesPerMonthWithAllMonths' => $revenuePerMonth,
            'totalYearlyRevenue' => $totalYearlyRevenue
        ]);
    }




    public function update(Request $request, $id)
    {
        try {

            $qtap_affiliate = qtap_affiliate::find($id);

            // التحقق من البيانات المدخلة
            $request->validate([
                'name' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'mobile' => 'nullable|string|max:255',
                'birth_date' => 'nullable|date',
                'status' => 'nullable|in:active,inactive',
                'email' => 'nullable|string|email|max:255',
                'password' => 'nullable|string|min:1',
                'user_type' => 'nullable|in:qtap_affiliates',
                'payment_way' => 'nullable|in:bank_account,wallet,credit_card',
            ]);

            // إذا تم تقديم كلمة مرور جديدة، نقوم بتشفيرها
            if ($request->password) {
                $request->merge(['password' => Hash::make($request->password)]);
            }

            // تحديث بيانات المستخدم
            $qtap_affiliate->update($request->all());

            // تحديث أو إنشاء بيانات طرق الدفع
            $paymentData = $request->only([
                'payment_way',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'wallet_provider',
                'wallet_number',
                'credit_card_number',
                'credit_card_holder_name',
                'credit_card_expiration_date'
            ]);

            $affiliatePaymentInfo = affiliate_payment_info::where('affiliate_id', $qtap_affiliate->id)->first();

            if ($affiliatePaymentInfo) {
                $affiliatePaymentInfo->update($paymentData);
            } else {


                affiliate_payment_info::create(array_merge($paymentData, [
                    'affiliate_id' => $qtap_affiliate->id,
                ]));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث البيانات بنجاح.',
            ], 200);
        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ آخر، نعرض رسالة الخطأ بالتفصيل
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء تحديث البيانات.',
                'error_details' => $e->getMessage(),
            ], 500);
        } catch (ValidationException $e) {
            // إذا كانت الأخطاء بسبب التحقق من صحة البيانات
            return response()->json([
                'status' => 'error',
                'message' => 'بعض البيانات غير مكتملة أو غير صحيحة.',
                'errors' => $e->errors(),
            ], 422);
        }
    }


    // داخل موديل affiliate_clicks


    public function Sales_clicks($year)
    {




        // الحصول على عدد النقرات في كل شهر من السنة المحددة
        $monthlyClicks = affiliate_clicks::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(id) as clicks_count')
            ->whereYear('created_at', $year)
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        // تعريف أسماء الشهور
        $allMonths = collect([
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ]);

        // بناء مصفوفة بعدد النقرات لكل شهر
        $clicksPerMonth = $allMonths->map(function ($monthName, $monthNumber) use ($monthlyClicks) {
            $data = $monthlyClicks->get($monthNumber);
            return [
                'month_name' => $monthName,
                'clicks_count' => $data ? $data->clicks_count : 0,
            ];
        })->values();









        // الحصول على عدد المستخدمين الجدد في كل شهر من السنة المحددة
        $monthlyUsers = qtap_clients_brunchs::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(id) as users_count')
            ->whereYear('created_at', $year)
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        // تعريف أسماء الشهور
        $allMonths = collect([
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ]);

        // بناء مصفوفة بعدد المستخدمين لكل شهر
        $usersPerMonth = $allMonths->map(function ($monthName, $monthNumber) use ($monthlyUsers) {
            $data = $monthlyUsers->get($monthNumber);
            return [
                'month_name' => $monthName,
                'users_count' => $data ? $data->users_count : 0,
            ];
        })->values();

        return response()->json([
            'year' => $year,
            'users_count_by_month' => $usersPerMonth,
            'clicksPerMonth' => $clicksPerMonth,
        ]);
    }



    public function get_sales_affiliate()
    {
        $userId = auth()->id();
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // عدد المبيعات
        $todaySalesCount = affiliate_Revenues::where('affiliate_id', $userId)
            ->whereDate('created_at', $today)
            ->count();

        $yesterdaySalesCount = affiliate_Revenues::where('affiliate_id', $userId)
            ->whereDate('created_at', $yesterday)
            ->count();

        // مجموع الأرباح
        $todaySalesAmount = affiliate_Revenues::where('affiliate_id', $userId)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $yesterdaySalesAmount = affiliate_Revenues::where('affiliate_id', $userId)
            ->whereDate('created_at', $yesterday)
            ->sum('amount');

        // حساب النسب (مع الحماية من القسمة على صفر)
        $salesCountChange = $yesterdaySalesCount > 0
            ? round((($todaySalesCount - $yesterdaySalesCount) / $yesterdaySalesCount) * 100, 2)
            : 0;

        $salesAmountChange = $yesterdaySalesAmount > 0
            ? round((($todaySalesAmount - $yesterdaySalesAmount) / $yesterdaySalesAmount) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'salesCount' => $todaySalesCount,
            'salesCountYesterday' => $yesterdaySalesCount,
            'salesCountChangePercent' => $salesCountChange, // ممكن تكون null لو ما فيه بيانات أمس
            'notes' => 'النسبة صفر يعني لا يوجد ارتفاع او انخفاض  في الطلبات او الارباح',
            'salesAmount' => $todaySalesAmount,
            'salesAmountYesterday' => $yesterdaySalesAmount,
            'salesAmountChangePercent' => $salesAmountChange, // ممكن تكون null لو ما فيه بيانات أمس
        ]);
    }



    public function get_affiliate_info($id)
    {

        $affiliate = qtap_affiliate::with('payment_info')->find($id);

        if (!$affiliate) {
            return response()->json([
                'error' => 'affiliate not found'
            ], 404);
        }


        return response()->json([
            'success' => true,
            'affiliate' => $affiliate
        ]);
    }


    public function destroy($id)
    {
        try {
            $qtap_affiliate = qtap_affiliate::find($id);

            if (!$qtap_affiliate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'البيانات غير موجودة.',
                ], 404);
            }
            $qtap_affiliate->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'تم حذف البيانات بنجاح.',
            ], 200);
        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ آخر، نعرض رسالة الخطاء بالتفصيل
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطاء اثناء حذف البيانات.',
                'error_details' => $e->getMessage(),
            ], 500);
        }
    }
}
