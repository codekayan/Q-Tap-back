<?php

namespace App\Http\Controllers;

use App\Models\clients_logs;

use App\Models\qtap_affiliate;
use App\Models\pricing;
use App\Models\qtap_admins;
use App\Models\qtap_clients;
use App\Models\qtap_clients_brunchs;
use App\Models\Revenue;
use Illuminate\Http\Request;


use App\Mail\active_account;
use App\Models\orders;
use Illuminate\Support\Facades\Mail;

use Carbon\Carbon;

class QtapAdminsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients_active = qtap_clients::where('status', 'active');
        $clients_inactive = qtap_clients::where('status', 'inactive');
        return response()->json([
            "success" => true,
            "clients_active" => $clients_active,
            "clients_inactive" => $clients_inactive
        ]);
    }




    public function active_clients($id)
    {
        $client = qtap_clients::with('brunchs')->find($id);

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $client->update(['status' => 'active']);

        // تحديث جميع الفروع دفعة واحدة
        qtap_clients_brunchs::where('client_id', $id)->update(['status' => 'active']);

        $client_after = qtap_clients::with('brunchs')->find($id);

       // Mail::to($client->email)->send(new active_account('تم تفعيل الحساب بنجاح'));

        return response()->json([
            'success' => true,
            'client' => $client_after
        ]);
    }


    public function Sales($year)
    {
        // تحقق من أن السنة المدخلة صحيحة
        if (!is_numeric($year) || $year < 1900 || $year > date('Y')) {
            return response()->json(['error' => 'سنة غير صحيحة.']);
        }

        // استعلام للحصول على عدد الأفرع وقيمة الأرباح لكل شهر في السنة المحددة
        // تأكد من أن العلاقة بين "qtap_clients_brunchs" و "revenue" مهيأة

        $branchesPerMonth = Revenue::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(value) as total_revenue') // جمع الأرباح
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



    public function Performance($startYear, $endYear)
    {
        // تحديد بداية ونهاية السنة الأولى (startYear)
        $startDate1 = $startYear . '-01-01';
        $endDate1 = $startYear . '-12-31';

        // تحديد بداية ونهاية السنة الثانية (endYear)
        $startDate2 = $endYear . '-01-01';
        $endDate2 = $endYear . '-12-31';

        // جلب الاشتراكات للسنة الأولى
        $Subscriptions1 = qtap_clients_brunchs::where('status', 'active')
            ->whereBetween('created_at', [$startDate1, $endDate1])
            ->count();

        // جلب الطلبات للسنة الأولى
        $Orders1 = qtap_clients_brunchs::where('status', 'active')
            ->whereBetween('created_at', [$startDate1, $endDate1])
            ->count();

        // جلب الاشتراكات للسنة الثانية
        $Subscriptions2 = qtap_clients_brunchs::where('status', 'active')
            ->whereBetween('created_at', [$startDate2, $endDate2])
            ->count();

        // جلب الطلبات للسنة الثانية
        $Orders2 = qtap_clients_brunchs::where('status', 'active')
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





public function Sales_by_days($days)
{
    // Validate input days
    if (!is_numeric($days) || $days <= 0) {
        return response()->json(['error' => 'Invalid number of days.']);
    }

    // Query to get branches count and revenue for each day in current year
    $revenuePerDay = Revenue::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, DAY(created_at) as day, SUM(value) as total_revenue, MIN(created_at) as created_at')
        ->whereYear('created_at', date('Y'))
        ->groupBy('year', 'month', 'day')
        ->orderBy('created_at', 'asc')
        ->get();

    // Format the data with date and revenue
    $revenueData = $revenuePerDay->map(function ($item) {
        return [
            'date' => $item->year . '-' . sprintf('%02d', $item->month) . '-' . sprintf('%02d', $item->day),
            'total_revenue' => $item->total_revenue,
        ];
    });

    // Calculate weeks
    $totalWeeks = ceil($days / 7);
    $remainingDays = $days % 7;

    // Group days into weeks
    $weeks = [];
    $currentWeek = 1;
    $currentWeekRevenue = 0;
    $currentDays = 0;

    for ($week = 1; $week <= $totalWeeks; $week++) {
        $currentWeekRevenue = 0;
        $currentWeekDays = 0;

        while ($currentDays < $days && $currentWeekDays < 7) {
            if (isset($revenueData[$currentDays])) {
                $currentWeekRevenue += $revenueData[$currentDays]['total_revenue'] ?? 0;
            }
            $currentDays++;
            $currentWeekDays++;
        }

        $weeks[] = [
            'week' => 'Week ' . $week,
            'start_date' => ($currentWeekDays > 0 && isset($revenueData[$currentDays - $currentWeekDays])) ?
                $revenueData[$currentDays - $currentWeekDays]['date'] : 'N/A',
            'end_date' => ($currentWeekDays > 0 && isset($revenueData[$currentDays - 1])) ?
                $revenueData[$currentDays - 1]['date'] : 'N/A',
            'revenue' => $currentWeekRevenue
        ];
    }

    return response()->json([
        'success' => true,
        'data' => $weeks,
        'total' => array_sum(array_column($weeks, 'revenue')),
        'period' => $days . ' days',
        'weeks_count' => $totalWeeks
    ]);
}




   /* public function dashboard()
    {

        // $clients_active = qtap_clients::with('logs' , 'brunchs.pricing')->where('status', 'active')->get();

        $clients_active = qtap_clients::with('logs', 'brunchs')->where('status', 'active')->get();

        $qtap_clients_brunchs = qtap_clients_brunchs::get();








        //---------------Clients Log chart------------------------------------------------------




        $today = Carbon::today(); // الحصول على تاريخ اليوم

        $Clients_Log = clients_logs::whereDate('created_at', $today)->get();





        //---------------Affiliate Users chart------------------------------------------------------

        // جلب عدد المسوقين الفعالين
        $activeAffiliates = \App\Models\qtap_affiliate::where('status', 'active')->count();

        // جلب العدد الكلي للمسوقين
        $totalAffiliates = \App\Models\qtap_affiliate::count();

        // حساب النسبة المئوية للمسوقين الفعالين
        $activePercentage = $totalAffiliates > 0 ? round(($activeAffiliates / $totalAffiliates) * 100, 2) : 0;

        // حساب النسبة المئوية للمسوقين الغير فعالين
        $inactivePercentage = $totalAffiliates > 0 ? round((($totalAffiliates - $activeAffiliates) / $totalAffiliates) * 100, 2) : 0;

        // تخزين النسب في مصفوفة واحدة
        $Affiliate_Users = [
            'totalAffiliates' => $totalAffiliates,
            'active_percentage' => $activePercentage . '%',
            'inactive_percentage' => $inactivePercentage . '%',
        ];






        //---------------Total Orders chart------------------------------------------------------


        $branchesPerMonth = orders::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(id) as total_branches')
            ->whereYear('created_at', date('Y')) // استعلام فقط للسنة الحالية
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month'); // استخدام keyBy لتحديد الشهر كمفتاح للمصفوفة

        // إنشاء مصفوفة تحتوي على جميع الأشهر
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

        // دمج الأشهر مع عدد الأفرع
        $branchesPerMonthWithAllMonths = $allMonths->map(function ($monthName, $monthNumber) use ($branchesPerMonth) {
            $branchData = $branchesPerMonth->get($monthNumber);

            return [
                'month_name' => $monthName,
                'total_order' => $branchData ? $branchData->total_branches : 0
            ];
        });





        //---------------Client chart------------------------------------------------------

        $allBranchesCount = qtap_clients_brunchs::count();

        $Client = pricing::withCount('qtap_clients_brunchs')->get()->map(function ($package) use ($allBranchesCount) {

            $package->percentage = $allBranchesCount > 0 ? round(($package->qtap_clients_brunchs_count / $allBranchesCount) * 100, 2) . '%' : 0 . '%';

            return $package;
        })->select('id', 'name', 'qtap_clients_brunchs_count', 'percentage');

        $Client['number_branches_clients'] = $allBranchesCount;

        //--------------------------------------------------------------------------------------------

        return response()->json([
            "success" => true,
            "Clients_Log" => $Clients_Log,
            "Client" => $Client,
            "Total_Orders" => $branchesPerMonthWithAllMonths,
            'total_orders' => $branchesPerMonthWithAllMonths->sum('total_order'),
            "Affiliate_Users" => $Affiliate_Users,
            // "clients_active" => $clients_active,
            // "qtap_clients_brunchs" => $qtap_clients_brunchs,
            // "affiliate" => $qtap_affiliate,
            // "clients_inactive" => $clients_inactive
        ]);
    }*/
        public function dashboard()
    {
        $clients_active = qtap_clients::with('logs', 'brunchs')->where('status', 'active')->get();
        $allBranchesCount = qtap_clients_brunchs::count();

        //---------------Clients Log chart------------------------------------------------------
        $today = Carbon::today();
        $Clients_Log = clients_logs::whereDate('created_at', $today)->get();

        //---------------Affiliate Users chart------------------------------------------------------
        $activeAffiliates = \App\Models\qtap_affiliate::where('status', 'active')->count();
        $totalAffiliates = \App\Models\qtap_affiliate::count();
        $activePercentage = $totalAffiliates > 0 ? round(($activeAffiliates / $totalAffiliates) * 100, 2) : 0;
        $inactivePercentage = $totalAffiliates > 0 ? round((($totalAffiliates - $activeAffiliates) / $totalAffiliates) * 100, 2) : 0;

        $Affiliate_Users = [
            'totalAffiliates' => $totalAffiliates,
            'active_percentage' => $activePercentage . '%',
            'inactive_percentage' => $inactivePercentage . '%',
        ];

        //---------------Total Orders chart------------------------------------------------------
        $branchesPerMonth = orders::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(id) as total_branches')
            ->whereYear('created_at', date('Y'))
            ->groupBy('year', 'month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

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
        $branchesPerMonthWithAllMonths = $allMonths->map(function ($monthName, $monthNumber) use ($branchesPerMonth) {
            $branchData = $branchesPerMonth->get($monthNumber);
            return [
                'month_name' => $monthName,
                'total_order' => $branchData ? $branchData->total_branches : 0
            ];
        });

        //---------------Client chart (Modified version without pricing-branch relationship)------
        $Client = [
            'number_branches_clients' => $allBranchesCount,
            'packages' => []
        ];

        // إذا كنت تريد إحصائيات الباقات من مكان آخر (مثل اشتراكات العملاء)
        if (class_exists(\App\Models\ClientPricing::class)) {
            $packageStats = \App\Models\ClientPricing::with('pricing')
                ->where('status', 'active')
                ->get()
                ->groupBy('pricing.name')
                ->map(function ($subscriptions, $name) use ($allBranchesCount) {
                    return [
                        'name' => $name,
                        'qtap_clients_brunchs_count' => $subscriptions->count(),
                        'percentage' => $allBranchesCount > 0 ?
                            round(($subscriptions->count() / $allBranchesCount) * 100, 2) . '%' : '0%'
                    ];
                });

            $Client['packages'] = $packageStats->values();
        }

        return response()->json([
            "success" => true,
            "Clients_Log" => $Clients_Log,
            "Client" => $Client,
            "Total_Orders" => $branchesPerMonthWithAllMonths,
            'total_orders' => $branchesPerMonthWithAllMonths->sum('total_order'),
            "Affiliate_Users" => $Affiliate_Users,
        ]);
    }







    public function wallet($year)
    {



        $Revenue = Revenue::whereYear('created_at', $year)->get();

        $Revenue = $Revenue->sum('value');

        return response()->json([
            "success" => true,
            "Revenue" => $Revenue,
            "Revenue_Percentage" => 10 . '%',
            "Expenses" => 0,
            "Expenses_Percentage" => 0 . '%',
            "Withdrawal" => 0,
            "Withdrawal_Percentage" => 0 . '%',
            "Balance" => $Revenue,
            "Balance_Percentage" => 10 . '%',
        ]);
    }






    public function Deposits($startDate, $endDate)
    {
        // تحويل التواريخ إلى بداية ونهاية اليوم لضمان الدقة
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();


        // جلب البيانات بناءً على المدة المحددة
        $Revenue = Revenue::with('client')->whereBetween('created_at', [$start, $end])->get();

        return response()->json([
            "success" => true,
            "Deposits" => $Revenue,
        ]);
    }


    public function qtap_clients()
    {

        $clients_active = qtap_clients::where('status', 'active');
        $clients_inactive = qtap_clients::where('status', 'inactive');
        return response()->json([
            "success" => true,
            "clients_active" => $clients_active,
            "clients_inactive" => $clients_inactive
        ]);
    }
}
