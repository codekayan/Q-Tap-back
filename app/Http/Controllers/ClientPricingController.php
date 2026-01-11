<?php

namespace App\Http\Controllers;

use App\Models\ClientPricing;
use App\Models\qtap_clients;
use App\Models\pricing;
use App\Models\coup_plan;

use App\Models\SubscriptionChangeRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\qtap_clients_brunchs;
class ClientPricingController extends Controller
{
    /**
     * عرض جميع الاشتراكات مع إمكانية الفلترة
     */
    public function index(Request $request)
    {
        $query = ClientPricing::with(['client', 'pricing'])
            ->latest();

        // تطبيق الفلاتر إذا وجدت
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('pricing_id')) {
            $query->where('pricing_id', $request->pricing_id);
        }

        if ($request->has('payment_methodes')) {
            $query->where('payment_methodes', $request->payment_methodes);
        }

        if ($request->has('pricing_way')) {
            $query->where('pricing_way', $request->pricing_way);
        }

        $subscriptions = $query->paginate(10);

        // بيانات إضافية للفلترة
        $clients = qtap_clients::select('id', 'name')->get();
        $pricings = pricing::select('id', 'name')->get();
        $statuses = ['active', 'inactive', 'expired', 'pending'];
        $paymentMethods = ['cash', 'credit_card', 'bank_transfer']; // تعديل حسب طرق الدفع المتاحة
        $pricingWays = ['monthly_price', 'yearly_price'];

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions,
            'filters' => [
                'clients' => $clients,
                'pricings' => $pricings,
                'statuses' => $statuses,
                'payment_methods' => $paymentMethods,
                'pricing_ways' => $pricingWays
            ]
        ]);
    }



    public function clientSubscriptions(Request $request)
    {
        try {
            // جلب العميل المسجل من نظام المصادقة باستخدام الجارد الصحيح
            $user = Auth::guard('restaurant_user_staff')->user();

            // التحقق من وجود المستخدم المصادق عليه
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Please login first'
                ], 401);
            }

            // البحث عن العميل في جدول qtap_clients باستخدام user_id
            $client = qtap_clients::where('email', $user->email)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $query = ClientPricing::with(['pricing' ,'client'])
                        ->where('client_id', $client->id)
                        ->latest();

            // تطبيق الفلاتر
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('pricing_id')) {
                $query->where('pricing_id', $request->pricing_id);
            }

            if ($request->has('payment_methodes')) {
                $query->where('payment_methodes', $request->payment_methodes);
            }

            if ($request->has('pricing_way')) {
                $query->where('pricing_way', $request->pricing_way);
            }

            $subscriptions = $query->paginate(10);

            // بيانات الفلترة
            $pricings = pricing::select('id', 'name')->get();
            $statuses = ['active', 'inactive', 'expired', 'pending'];
            $paymentMethods = ['cash', 'credit_card', 'bank_transfer'];
            $pricingWays = ['monthly_price', 'yearly_price'];

            return response()->json([
                'success' => true,
                'subscriptions' => $subscriptions,
                'filters' => [
                    'pricings' => $pricings,
                    'statuses' => $statuses,
                    'payment_methods' => $paymentMethods,
                    'pricing_ways' => $pricingWays
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show_C($id)
    {
        try {
            // جلب العميل المسجل من نظام المصادقة
            $user = Auth::guard('restaurant_user_staff')->user();

            // التحقق من وجود المستخدم المصادق عليه
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Please login first'
                ], 401);
            }

            // البحث عن العميل في جدول qtap_clients
            $client = qtap_clients::where('email', $user->email)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            // جلب الاشتراك مع التحقق أنه يخص هذا العميل فقط
            $subscription = ClientPricing::with(['pricing'])
                            ->where('id', $id)
                            ->where('client_id', $client->id)
                            ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found or does not belong to you'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'subscription' => $subscription
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }



public function activateSubscription($id)
{
    DB::beginTransaction();
    try {
        $subscription = ClientPricing::with('client')->findOrFail($id);

        // التحقق من عدم وجود اشتراك نشط آخر لنفس العميل
        $activeSubscription = ClientPricing::where('client_id', $subscription->client_id)
            ->where('status', 'active')
            ->where('id', '!=', $id) // استثناء الاشتراك الحالي
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot activate subscription - client already has an active subscription',
                'active_subscription_id' => $activeSubscription->id
            ], 400);
        }

        // حساب تاريخ الانتهاء بناءً على pricing_way
        $expiredAt = null;
        if ($subscription->pricing_way === 'monthly_price') {
            $expiredAt = Carbon::now()->addMonth();
        } elseif ($subscription->pricing_way === 'yearly_price') {
            $expiredAt = Carbon::now()->addYear();
        }

        $subscription->update([
            'status' => 'active',
            'expired_at' => $expiredAt
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Subscription activated successfully',
            'subscription' => $subscription
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to activate subscription',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * عرض اشتراك معين
     */
    public function show($id)
    {
        $subscription = ClientPricing::with(['client', 'pricing'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'subscription' => $subscription
        ]);
    }

    /**
     * تحديث بيانات الاشتراك
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'pricing_id' => 'sometimes|exists:pricings,id',
            'status' => 'sometimes|in:Active,Inactive,Expired,Pending',
            'payment_methodes' => 'sometimes|string',
            'pricing_way' => 'sometimes|in:monthly_price,yearly_price',
            'ramin_order' => 'sometimes|integer'
        ]);

        $subscription = ClientPricing::findOrFail($id);
        $subscription->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الاشتراك بنجاح',
            'subscription' => $subscription
        ]);
    }

    /**
     * حذف اشتراك
     */
    public function destroy($id)
    {
        $subscription = ClientPricing::findOrFail($id);
        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الاشتراك بنجاح'
        ]);
    }



   /* public function requestSubscriptionChange(Request $request, $clinet_pricing_id)
    {
        $user = Auth::guard('restaurant_user_staff')->user();

        // التحقق من وجود المستخدم المصادق عليه
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Please login first'
            ], 401);
        }

        // البحث عن العميل في جدول qtap_clients
            $client = qtap_clients::where('email', $user->email)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        // جلب الاشتراك مع التحقق أنه يخص هذا العميل فقط
        $subscription = ClientPricing::with(['pricing' ,'client'])
                        ->where('id', $clinet_pricing_id)
                        ->where('client_id', $client->id)
                        ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found or does not belong to you'
            ], 404);
        }

        // التحقق من وجود طلب معلق لنفس الاشتراك
        $existingRequest = SubscriptionChangeRequest::where('client_pricing_id', $clinet_pricing_id)
                            ->where('status', 'pending')
                            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending request for this subscription. Please wait for admin approval.',
                'existing_request_id' => $existingRequest->id
            ], 400);
        }

        $request->validate([
            'action_type' => 'required|in:renew,upgrade',
            'new_pricing_id' => 'required_if:action_type,upgrade,|exists:pricings,id',
            'pricing_way' => 'required|in:monthly_price,yearly_price',
            'payment_method' => 'required|in:cash,wallet',

        ]);

        // إنشاء طلب معلق بانتظار الموافقة
        $changeRequest = SubscriptionChangeRequest::create([
            'client_pricing_id' => $clinet_pricing_id,
            'action_type' => $request->action_type,
            'new_pricing_id' => $request->new_pricing_id ?? $subscription->pricing_id,
            'pricing_way' => $request->pricing_way,
            'payment_methodes' => $request->payment_method,

            'status' => 'pending',
            'requested_at' => now()
        ]);

        // إرسال إشعار للأدمن
        // Admin::all()->each->notify(new NewSubscriptionChangeRequest($changeRequest));

        return response()->json([
            'success' => true,
            'message' => 'Change request submitted. Waiting for admin approval.',
            'request_id' => $changeRequest->id
        ]);
    }*/
public function requestSubscriptionChange(Request $request, $clinet_pricing_id)
{
    $user = Auth::guard('restaurant_user_staff')->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized - Please login first'
        ], 401);
    }

    $client = qtap_clients::where('email', $user->email)->first();

    if (!$client) {
        return response()->json([
            'success' => false,
            'message' => 'Client not found'
        ], 404);
    }

    $subscription = ClientPricing::with(['pricing','client'])
                    ->where('id', $clinet_pricing_id)
                    ->where('client_id', $client->id)
                    ->first();

    if (!$subscription) {
        return response()->json([
            'success' => false,
            'message' => 'Subscription not found or does not belong to you'
        ], 404);
    }

    $existingRequest = SubscriptionChangeRequest::where('client_pricing_id', $clinet_pricing_id)
                        ->where('status', 'pending')
                        ->first();

    if ($existingRequest) {
        return response()->json([
            'success' => false,
            'message' => 'You already have a pending request for this subscription.',
            'existing_request_id' => $existingRequest->id
        ], 400);
    }

    $request->validate([
        'action_type' => 'required|in:renew,upgrade',
        'new_pricing_id' => 'required_if:action_type,upgrade|exists:pricings,id',
        'pricing_way' => 'required|in:monthly_price,yearly_price',
        'payment_method' => 'required|in:cash,wallet',
        'coupon_code' => 'sometimes|string|exists:coup_plans,code'
    ]);

    // حساب السعر مع الخصم إذا وجد
    $pricing = $request->action_type == 'upgrade' 
        ? pricing::find($request->new_pricing_id)
        : $subscription->pricing;

    if (!$pricing) {
        return response()->json([
            'success' => false,
            'message' => 'Pricing plan not found'
        ], 404);
    }

    // حساب عدد الفروع الحالية للعميل
    $number_of_branches = qtap_clients_brunchs::where('client_id', $client->id)->count();
    $number_of_branches = max(1, $number_of_branches); // التأكد من أن العدد لا يقل عن 1

    // حساب السعر الأصلي مع مراعاة عدد الفروع
    $service_cost = $pricing->{$request->pricing_way};
    $original_total_cost = match (true) {
        $number_of_branches == 2 => floatval($service_cost * 1.5),
        $number_of_branches == 3 => floatval($service_cost * 2),
        $number_of_branches > 3 => intdiv($number_of_branches, 2) * floatval($service_cost * 1.5) + ($number_of_branches % 2 ? floatval($service_cost * 2) : 0),
        default => $service_cost
    };

    $discountPercentage = 0;
    $discountAmount = 0;
    $finalPrice = $original_total_cost;

    if ($request->filled('coupon_code')) {
        $coupon = coup_plan::where('code', $request->coupon_code)
                        ->where('status', 'active')
                        ->first();

        if ($coupon) {
            $discountPercentage = $coupon->discount;
            $discountAmount = $original_total_cost * ($discountPercentage / 100);
            $finalPrice = $original_total_cost - $discountAmount;
        }
    }

    $finalPrice = ceil(max(0, $finalPrice)); // التأكد من أن السعر النهائي ليس سالبًا

    $changeRequest = SubscriptionChangeRequest::create([
        'client_pricing_id' => $clinet_pricing_id,
        'action_type' => $request->action_type,
        'new_pricing_id' => $request->new_pricing_id ?? $subscription->pricing_id,
        'pricing_way' => $request->pricing_way,
        'payment_methodes' => $request->payment_method,
        'coupon_code' => $request->coupon_code ?? null,
        'original_price' => $service_cost,
        'original_total_price' => $original_total_cost,
        'discount_percentage' => $discountPercentage,
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,
        'number_of_branches' => $number_of_branches,
        'status' => 'pending',
        'requested_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Change request submitted. Waiting for admin approval.',
        'request_id' => $changeRequest->id,
        'price_details' => [
            'original_price' => $service_cost,
            'original_total' => $original_total_cost,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'coupon_code' => $request->coupon_code ?? null,
            'number_of_branches' => $number_of_branches
        ]
    ]);
}




    public function getPendingChangeRequests(Request $request)
    {
        try {
            // التحقق من المصادقة
            $user = Auth::guard('restaurant_user_staff')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // جلب العميل مع التحقق من وجوده
            $client = qtap_clients::where('email', $user->email)->first();
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client account not found'
                ], 404);
            }

            // بناء الاستعلام مع العلاقات
            $requests = SubscriptionChangeRequest::with([
                    'ClientPricing.pricing', // الاشتراك الحالي وباقته
                    'ClientPricing.client',
                    'Pricing' // الباقة الجديدة (إذا وجدت)
                ])
                ->whereHas('ClientPricing', function($query) use ($client) {
                    $query->where('client_id', $client->id);
                })
                ->where('status', 'pending')
                ->latest('requested_at')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'requests' => $requests,
                'message' => 'Pending requests retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function cancelPendingRequest(Request $request, $requestId)
    {
        try {
            // التحقق من المصادقة
            $user = Auth::guard('restaurant_user_staff')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // جلب العميل - التعديل هنا لاستخدام user_id بدلاً من البريد
            $client = qtap_clients::find($user->user_id);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client account not found'
                ], 404);
            }

            // البحث عن الطلب مع التحقق أنه يخص العميل وأنه pending
            $changeRequest = SubscriptionChangeRequest::with(['ClientPricing'])
                ->where('id', $requestId)
                ->where('status', 'pending')
                ->whereHas('ClientPricing', function($query) use ($client) {
                    $query->where('client_id', $client->id);
                })
                ->first();

            if (!$changeRequest) {
                // إضافة لوج للتحقق من الخطأ
                Log::error('Failed to find request', [
                    'requestId' => $requestId,
                    'clientId' => $client->id,
                    'existingRequests' => SubscriptionChangeRequest::where('id', $requestId)->get()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Request not found, already processed, or does not belong to you'
                ], 404);
            }

            // التعديل هنا: استخدام soft delete إذا كان مفعلاً أو حذف نهائي
            $changeRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Request cancelled successfully',
                'cancelled_request' => $changeRequest
            ]);

        } catch (\Exception $e) {
            Log::error('Error cancelling request: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel request',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function getPendingChangeRequestsAdmin(Request $request)
    {
        try {

            // بناء الاستعلام مع العلاقات
            $requests = SubscriptionChangeRequest::with([
                    'ClientPricing.pricing', // الاشتراك الحالي وباقته
                    'ClientPricing.client',
                    'Pricing' // الباقة الجديدة (إذا وجدت)
                ])
                ->latest('requested_at')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'requests' => $requests,
                'message' => 'Pending requests retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function showPendingRequestAdmin($id)
    {
        try {
            // البحث عن الطلب مع العلاقات المرتبطة
            $request = SubscriptionChangeRequest::with([
                'ClientPricing.client',  // الاشتراك الحالي والعميل
                'ClientPricing.pricing', // الباقة الحالية
                'Pricing'                // الباقة الجديدة (إذا وجدت)
            ])->find($id);

            if (!$request) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'request' => $request,
                'message' => 'Request details retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve request details',
                'error' => $e->getMessage()
            ], 500);
        }
    }


















  /*  public function updateRequestStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // التحقق من صحة البيانات الأساسية
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected',
            ]);

            // جلب طلب التغيير مع العلاقات مع معالجة حالة عدم الوجود
            $changeRequest = SubscriptionChangeRequest::with([
                'ClientPricing.pricing',
                'Pricing',
                'ClientPricing.client'
            ])->find($id);

            if (!$changeRequest) {
                throw new \Exception('Subscription change request not found', 404);
            }

            // التحقق من أن الطلب معلق
            if ($changeRequest->status != 'pending') {
                throw new \Exception('This request has already been processed', 400);
            }

            // تحديث حالة الطلب
            $changeRequest->update(['status' => $validated['status']]);

            // إذا تم الرفض
            if ($validated['status'] == 'rejected') {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Request has been rejected successfully',
                    'data' => $changeRequest
                ]);
            }

            // معالجة حالة الموافقة
            $currentSub = $changeRequest->ClientPricing;
            $client = $currentSub->client;
            $currentPricing = $currentSub->pricing;
            $newPricing = $changeRequest->Pricing;

            // التحقق من وجود البيانات الأساسية
            if (!$currentSub || !$client) {
                throw new \Exception('Client subscription data is invalid', 400);
            }

            // التحقق من أن الاشتراك المستقبل غير inactive
            if ($changeRequest->action_type == 'upgrade' && $newPricing->is_active != 'active') {
                throw new \Exception('Cannot upgrade to an inactive pricing plan', 400);
            }

            // معالجة الدفع أولاً
            if ($changeRequest->payment_methodes == 'wallet') {
                if (!$newPricing) {
                    throw new \Exception('Pricing information is missing for wallet payment', 400);
                }

                $amount = $changeRequest->pricing_way == 'monthly_price'
                    ? $newPricing->monthly_price
                    : $newPricing->yearly_price;

                if ($client->wallet_balance < $amount) {
                    throw new \Exception('Your wallet balance is insufficient for this operation', 400);
                }

                // خصم المبلغ من المحفظة
                $client->decrement('wallet_balance', $amount);
            }

            // معالجة التجديد
            if ($changeRequest->action_type == 'renew') {
                if (!$currentPricing || $currentPricing->is_active != 'active') {
                    throw new \Exception('Current pricing plan is no longer available', 400);
                }

                $isExpired = $currentSub->status == 'expired';
                $currentOrdersLimit = $currentPricing->orders_limit;

                if ($isExpired) {
                    $currentSub->update([
                        'ramin_order' => $currentOrdersLimit,
                        'expired_at' => $changeRequest->pricing_way == 'monthly_price'
                            ? now()->addMonth()
                            : now()->addYear(),
                        'status' => 'active',
                        'payment_methodes' => $changeRequest->payment_methodes,
                        'pricing_way' => $changeRequest->pricing_way
                    ]);
                } else {
                    $remainingDays = max(0, Carbon::now()->diffInDays($currentSub->expired_at, false));
                    $totalPeriod = $currentSub->pricing_way == 'monthly_price' ? 30 : 365;

                    $currentSub->update([
                        'ramin_order' => $currentSub->ramin_order + $currentOrdersLimit,
                        'expired_at' => $changeRequest->pricing_way == 'monthly_price'
                            ? now()->addMonth()->addDays($remainingDays)
                            : now()->addYear()->addDays($remainingDays),
                        'payment_methodes' => $changeRequest->payment_methodes,
                        'pricing_way' => $changeRequest->pricing_way
                    ]);
                }
            }
            // معالجة الترقية
            elseif ($changeRequest->action_type == 'upgrade') {
                if (!$newPricing || $newPricing->is_active == 'inactive') {
                    throw new \Exception('The new pricing plan is no longer available', 400);
                }

                $remainingValue = $this->calculateRemainingValue($currentSub);
                $newOrdersLimit = $newPricing->orders_limit;

                $newSubscription = ClientPricing::create([
                    'client_id' => $currentSub->client_id,
                    'pricing_id' => $newPricing->id,
                    'status' => 'active',
                    'ramin_order' => $newOrdersLimit + $remainingValue,
                    'expired_at' => $changeRequest->pricing_way == 'monthly_price'
                        ? now()->addMonth()
                        : now()->addYear(),
                    'payment_methodes' => $changeRequest->payment_methodes,
                    'pricing_way' => $changeRequest->pricing_way,
                    'previous_subscription_id' => $currentSub->id
                ]);

                $currentSub->update([
                    'status' => 'inactive',
                    'upgraded_to' => $newSubscription->id
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request processed successfully',
                'data' => $changeRequest->fresh(['ClientPricing', 'Pricing'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            $statusCode = 500;
            $errorMessage = 'An unexpected error occurred';

            if ($e->getCode() >= 400 && $e->getCode() < 500) {
                $statusCode = $e->getCode();
                $errorMessage = $e->getMessage();
            }

            Log::error('Subscription change error: ' . $e->getMessage(), [
                'exception' => $e,
                'request_id' => $id ?? null,
                'user_id' => auth()->id() ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_details' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
        }
    }*/
        public function updateRequestStatus(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $changeRequest = SubscriptionChangeRequest::with([
            'ClientPricing.pricing',
            'Pricing',
            'ClientPricing.client'
        ])->find($id);

        if (!$changeRequest) {
            throw new \Exception('Subscription change request not found', 404);
        }

        if ($changeRequest->status != 'pending') {
            throw new \Exception('This request has already been processed', 400);
        }

        $changeRequest->update(['status' => $validated['status']]);

        if ($validated['status'] == 'rejected') {
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Request has been rejected successfully',
                'data' => $changeRequest
            ]);
        }

        $currentSub = $changeRequest->ClientPricing;
        $client = $currentSub->client;
        $currentPricing = $currentSub->pricing;
        $newPricing = $changeRequest->Pricing;

        if (!$currentSub || !$client) {
            throw new \Exception('Client subscription data is invalid', 400);
        }

        if ($changeRequest->action_type == 'upgrade' && $newPricing->is_active != 'active') {
            throw new \Exception('Cannot upgrade to an inactive pricing plan', 400);
        }

        // استخدام السعر النهائي بعد الخصم من الطلب
        $amount = $changeRequest->final_price;

        if ($changeRequest->payment_methodes == 'wallet') {
            if ($client->wallet_balance < $amount) {
                throw new \Exception('Your wallet balance is insufficient for this operation', 400);
            }

            $client->decrement('wallet_balance', $amount);
        }

        if ($changeRequest->action_type == 'renew') {
            if (!$currentPricing || $currentPricing->is_active != 'active') {
                throw new \Exception('Current pricing plan is no longer available', 400);
            }

            $isExpired = $currentSub->status == 'expired';
            $currentOrdersLimit = $currentPricing->orders_limit;

            $updateData = [
                'payment_methodes' => $changeRequest->payment_methodes,
                'pricing_way' => $changeRequest->pricing_way,
                'coupon_code' => $changeRequest->coupon_code,
                'discount_percentage' => $changeRequest->discount_percentage,
                'final_price' => $changeRequest->final_price
            ];

            if ($isExpired) {
                $updateData['ramin_order'] = $currentOrdersLimit;
                $updateData['expired_at'] = $changeRequest->pricing_way == 'monthly_price'
                    ? now()->addMonth()
                    : now()->addYear();
                $updateData['status'] = 'active';
            } else {
                $remainingDays = max(0, Carbon::now()->diffInDays($currentSub->expired_at, false));
                $updateData['ramin_order'] = $currentSub->ramin_order + $currentOrdersLimit;
                $updateData['expired_at'] = $changeRequest->pricing_way == 'monthly_price'
                    ? now()->addMonth()->addDays($remainingDays)
                    : now()->addYear()->addDays($remainingDays);
            }

            $currentSub->update($updateData);
        }
        elseif ($changeRequest->action_type == 'upgrade') {
            if (!$newPricing || $newPricing->is_active == 'inactive') {
                throw new \Exception('The new pricing plan is no longer available', 400);
            }

            $remainingValue = $this->calculateRemainingValue($currentSub);
            $newOrdersLimit = $newPricing->orders_limit;

        // في جزء الترقية (upgrade)
            $newSubscription = ClientPricing::create([
                'client_id' => $currentSub->client_id,
                'pricing_id' => $newPricing->id,
                'status' => 'active',
                'ramin_order' => $newOrdersLimit + $remainingValue,
                'expired_at' => $changeRequest->pricing_way == 'monthly_price'
                    ? now()->addMonth()
                    : now()->addYear(),
                'payment_methodes' => $changeRequest->payment_methodes,
                'pricing_way' => $changeRequest->pricing_way,
                'previous_subscription_id' => $currentSub->id,
                'coupon_code' => $changeRequest->coupon_code,
                'discount_percentage' => $changeRequest->discount_percentage,
                'final_price' => $changeRequest->final_price,
                'original_price' => $changeRequest->original_price,
                'original_total_price' => $changeRequest->original_price, // أو حسب حساباتك
                'discounted_price' => $changeRequest->final_price, // أضف هذا السطر
                'number_of_branches' => $currentSub->number_of_branches ?? 1
            ]);
        $currentSub->update([
                    'status' => 'inactive',
                    'upgraded_to' => $newSubscription->id
                ]);
            }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Request processed successfully',
            'data' => $changeRequest->fresh(['ClientPricing', 'Pricing']),
            'price_details' => [
                'original_price' => $changeRequest->original_price,
                'discount_percentage' => $changeRequest->discount_percentage,
                'discount_amount' => $changeRequest->discount_amount,
                'final_price' => $changeRequest->final_price,
                'coupon_code' => $changeRequest->coupon_code
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'error_details' => config('app.debug') ? $e->getMessage() : null
        ], $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500);
    }
}

    protected function calculateRemainingValue($subscription)
    {
        try {
            if (!$subscription || !$subscription->pricing) {
                throw new \Exception('Invalid subscription data');
            }

            if ($subscription->status == 'expired') {
                return 0;
            }

            if (!$subscription->expired_at || Carbon::now()->gt($subscription->expired_at)) {
                return 0;
            }

            $totalPeriod = $subscription->pricing_way == 'monthly_price' ? 30 : 365;
            $remainingDays = max(0, Carbon::now()->diffInDays($subscription->expired_at, false));

            if ($totalPeriod <= 0) {
                return 0;
            }

            $remainingPercentage = $remainingDays / $totalPeriod;
            $remainingValue = (1 - $remainingPercentage) * $subscription->pricing->orders_limit;

            return max(0, (int) round($remainingValue));

        } catch (\Exception $e) {
            Log::error('Remaining value calculation failed: ' . $e->getMessage());
            return 0;
        }
    }
}
