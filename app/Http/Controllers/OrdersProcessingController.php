<?php

namespace App\Http\Controllers;

use App\Models\orders_processing;
use App\Models\delivery_rider;
use App\Models\restaurant_user_staff;
use App\Models\orders;
use App\Models\meals;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;


use App\Events\notify_msg;
use App\Models\ClientPricing;
use App\Models\qtap_clients_brunchs;
use Illuminate\Support\Facades\DB;

class OrdersProcessingController extends Controller
{

    public function order_map($order_id)
    {

        $order_map = orders_processing::with('user')->where('order_id', $order_id)->get();

        if (!$order_map) {
            return response()->json([
                'success' => false,
                'message' => 'Order not accepted yet',
            ]);
        }

        return response()->json([
            'success' => true,
            'order_map' => $order_map,
        ]);
    }


    //----------------------get data-------------------------------------------------------------------------



    public function get_proccessing_orders($id)
    {

        $orders = orders_processing::with('order')->find($id);

        if (!$orders) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }


    public function get_new_orders()
    {
        $brunch_id = auth()->user()->brunch_id;

        $orders = orders::whereNotIn('id', function ($query) {
            $query->select('order_id')
                ->from('orders_processings');
        })->where('brunch_id', $brunch_id)->where('status', 'pending')->get();

        return response()->json($orders);

        return response()->json([
            'success' => true,
            'new_orders' => $orders,
        ]);
    }


    public function get_accepted_orders()
    {
        $brunch_id = auth()->user()->brunch_id;


        // $orders = orders::with('orders_processing')->whereNotIn('id', function ($query) {
        //     $query->select('order_id')
        //         ->from('orders_processings')
        //         ->where('status', 'payment_received');
        // })->where('brunch_id', $brunch_id)->where('status', 'pending')->get();


        $orders = Orders::with('orders_processing')
            ->where('brunch_id', $brunch_id)
            ->where('status', 'pending')
            ->whereHas('orders_processing', function ($query) {
                $query->where('status', 'accepted');
            })
            ->get();




        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No orders dine in or take away found',
            ]);
        }



        return response()->json([
            'success' => true,
            'accepted_orders' => $orders,
        ]);
    }



    public function get_prepared_orders()
    {

        $brunch_id = auth()->user()->brunch_id;

        $orders = orders::with('orders_processing')->whereNotIn('id', function ($query) {
            $query->select('order_id') // افترض أن "order_id" هو العمود الذي يربط بين orders و orders_processings
                ->from('orders_processings')
                ->whereIn('status', ['served', 'delivered']);
        })->where('brunch_id', $brunch_id)->where('status', 'pending')->where('type', '!=', 'delivery')->get();




        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No orders dine in or take away found',
            ]);
        }

        return response()->json([
            'success' => true,
            'prepared_orders' => $orders,
        ]);
    }



    public function get_prepared_orders_delivery()
    {
        $user = auth()->user();
        $brunch_id = $user->brunch_id;

        // استعلام للبحث عن الطلبات التي ليست في حالات "served" أو "delivered"
        $orders = orders::with('orders_processing')
            ->whereNotIn('id', function ($query) {
                $query->select('order_id') // ربط الطلب بـ order_id في جدول orders_processings
                    ->from('orders_processings')
                    ->whereIn('status', ['served', 'delivered']); // التحقق من أن الحالة ليست "served" أو "delivered"
            })
            ->where('brunch_id', $brunch_id)
            ->where('status', 'pending')->where('type', 'delivery')
            ->get();

        // التحقق إذا كانت النتائج فارغة
        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No orders delivery found',
            ]);
        }

        // إرجاع الطلبات
        return response()->json([
            'success' => true,
            'prepared_orders' => $orders,
        ]);
    }



    public function get_served_orders()
    {
        $brunch_id = auth()->user()->brunch_id;

        $orders = orders::with('orders_processing')
            ->whereHas('orders_processing', function ($query) {
                $query->where('status', 'served'); // التحقق من حالة "served"
            })
            ->where('brunch_id', $brunch_id)
            ->where('status', 'pending')
            ->get();


        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No orders served found',
            ]);
        }

        return response()->json([
            'success' => true,
            'served_orders' => $orders,
        ]);
    }



    public function get_delivered_orders()
    {
        $brunch_id = auth()->user()->brunch_id;
        $orders = orders_processing::where('status', 'delivered')->where('brunch_id', $brunch_id)->get();

        return response()->json([
            'success' => true,
            'delivered_orders' => $orders,
        ]);
    }

    //-----------------------------------------------------------------------------------------------


    public function accept_order(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'chef';


        $order = orders_processing::with([
            'orders_processing',
            'orders_processing.user'
        ])->where('order_id', $request->order_id)->where('status', 'accepted')->first();

        if ($order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already accepted',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:accepted,rejected',
            'stage' => 'required|in:chef',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());

        if ($request['status'] == 'rejected') {

            return response()->json([
                'success' => true,
                'message' => 'Order rejected successfully',
                // 'orders_processing' => $orders_processing,
            ]);
        }

        $type = 'accepted_order';
        $order = orders::with([
            'orders_processing',
            'orders_processing.user'
        ])->find($orders_processing->order_id);

        $order = [$order];
        event(new notify_msg($order, $type));
        return response()->json([
            'success' => true,
            'message' => 'Order accepted successfully',
            'orders_processing' => $orders_processing,
        ]);
    }



    public function order_prepared(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'chef';


        $order = orders_processing::where('order_id', $request->order_id)->where('status', 'prepared')->first();

        if ($order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already prepared',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:prepared,rejected',
            'stage' => 'required|in:chef',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());


        if ($request['status'] == 'rejected') {

            return response()->json([
                'success' => true,
                'message' => 'Order rejected successfully',
                // 'orders_processing' => $orders_processing,
            ]);
        }


        $type = 'prepared_order';




        $order = orders::with([
            'orders_processing',
            'orders_processing.user'
        ])->find($orders_processing->order_id);


        $order = [$order];


        event(new notify_msg($order, $type));

        return response()->json([
            'success' => true,
            'message' => 'Order prepared successfully',
            'orders_processing' => $orders_processing,
        ]);
    }







    public function payment_received(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'cashier';


        $order = orders_processing::where('order_id', $request->order_id)->where('status', 'payment_received')->first();

        if ($order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already payment received',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:payment_received,rejected',
            'stage' => 'required|in:cashier',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $order = orders::find($request->order_id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ]);
        }

        $order->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);


        $orders_processing = orders_processing::create($request->all());

        if ($request['status'] == 'rejected') {

            return response()->json([
                'success' => true,
                'message' => 'Order rejected successfully',
                // 'orders_processing' => $orders_processing,
            ]);
        }


        $type = 'payment_received_order';

        // $order = orders::with('orders_processing')->where('id', $orders_processing->order_id)->get();


        $order = orders::with([
            'orders_processing',
            'orders_processing.user'
        ])->find($orders_processing->order_id);


        $order = [$order];


        event(new notify_msg($order, $type));

        return response()->json([
            'success' => true,
            'message' => 'Order payment_received successfully',
            'orders_processing' => $orders_processing,
        ]);
    }





    public function order_served(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'waiter';


        $order = orders_processing::where('order_id', $request->order_id)->where('status', 'served')->first();

        if ($order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already served',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:served,rejected',
            'stage' => 'required|in:waiter',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }



        $orders_processing = orders_processing::create($request->all());

        if ($request['status'] == 'rejected') {

            return response()->json([
                'success' => true,
                'message' => 'Order rejected successfully',
                // 'orders_processing' => $orders_processing,
            ]);
        }


        $type = 'served_order';

        // $order = orders::with('orders_processing')->where('id', $orders_processing->order_id)->get();


        $order = orders::with([
            'orders_processing',
            'orders_processing.user'
        ])->find($orders_processing->order_id);


        $order = [$order];


        event(new notify_msg($order, $type));

        return response()->json([
            'success' => true,
            'message' => 'Order served successfully',
            'orders_processing' => $orders_processing,
        ]);
    }



    public function choose_delivery(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'delivery';


        $order = orders_processing::where('order_id', $request->order_id)->where('status', 'delivery')->first();

        if ($order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already assigned',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'delivery_rider_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:delivery,rejected',
            'stage' => 'required|in:delivery',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());

        if ($request['status'] == 'rejected') {

            return response()->json([
                'success' => true,
                'message' => 'Order rejected successfully',
                // 'orders_processing' => $orders_processing,
            ]);
        }


        $type = 'choose_delivery_order';

        // $order = orders::with('orders_processing')->where('id', $orders_processing->order_id)->get();


        $order = orders::with([
            'orders_processing',
            'orders_processing.user'
        ])->find($orders_processing->order_id);



        $order = [$order];


        event(new notify_msg($order, $type));

        return response()->json([
            'success' => true,
            'message' => 'Order assigned successfully',
            'orders_processing' => $orders_processing,
        ]);
    }


    public function order_done(Request $request)
    {
        DB::beginTransaction();

        try {
            $request['user_id'] = auth()->user()->id;
            $request['brunch_id'] = auth()->user()->brunch_id;
            $request['stage'] = 'done';

            // التحقق من عدم وجود طلب مكتمل بنفس الـ order_id
            $order = orders_processing::where('order_id', $request->order_id)
                    ->where('status', 'done')
                    ->first();

            if ($order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already done',
                ]);
            }

            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'user_id' => 'required|exists:restaurant_user_staffs,id',
                'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
                'status' => 'required|in:done,rejected',
                'stage' => 'required|in:done',
                'note' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ]);
            }

            // إنشاء سجل معالجة الطلب
            $orders_processing = orders_processing::create($request->all());

            // إذا كان الطلب مرفوضاً
            if ($request['status'] == 'rejected') {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Order rejected successfully',
                ]);
            }

            // جلب الاشتراك النشط للفرع
            $brunch = qtap_clients_brunchs::find($request->brunch_id);
            $subscription = ClientPricing::where('client_id', $brunch->client_id)
                            ->where('status', 'active')
                            ->first();

            if ($subscription) {
                // خصم order واحد من الاشتراك
                $subscription->decrement('ramin_order');

                // إذا وصل عدد الطلبات إلى صفر، نقوم بتعطيل الاشتراك
                if ($subscription->ramin_order <= 0) {
                    $subscription->update(['status' => 'expired']);
                }
            }

            // إرسال الإشعار
            $type = 'done_order';
            $order = orders::with(['orders_processing', 'orders_processing.user'])
                    ->find($orders_processing->order_id);

            event(new notify_msg([$order], $type));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order done successfully',
                'orders_processing' => $orders_processing,
                'remaining_orders' => $subscription ? $subscription->ramin_order : null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }



  /*  public function order_done(Request $request)
    {

        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'done';


        $order = orders_processing::where('order_id', $request->order_id)->where('status', 'done')->first();

        if ($order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already done',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:done,rejected',
            'stage' => 'required|in:done',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $orders_processing = orders_processing::create($request->all());


        if ($request['status'] == 'rejected') {

            return response()->json([
                'success' => true,
                'message' => 'Order rejected successfully',
                // 'orders_processing' => $orders_processing,
            ]);
        }


        $type = 'done_order';

        // $order = orders::with('orders_processing')->where('id', $orders_processing->order_id)->get();


        $order = orders::with([
            'orders_processing',
            'orders_processing.user'
        ])->find($orders_processing->order_id);



        $order = [$order];


        event(new notify_msg($order, $type));

        return response()->json([
            'success' => true,
            'message' => 'Order done successfully',
            'orders_processing' => $orders_processing,
        ]);
    }*/



    public function order_delivered(Request $request)
    {
        $request['user_id'] = auth()->user()->id;
        $request['brunch_id'] = auth()->user()->brunch_id;
        $request['stage'] = 'delivery';




        $user = auth()->user();

        if ($user->status_rider == 'Busy') {
            return response()->json([
                'success' => false,
                'message' => 'You are not available',
            ]);
        }

        $order = orders_processing::where('order_id', $request->order_id)->where('status', 'delivered')->first();
        if ($order) {
            return response()->json([
                'success' => false,
                'message' => 'Order already delivered',
            ]);
        }


        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:restaurant_user_staffs,id',
            'brunch_id' => 'required|exists:qtap_clients_brunchs,id',
            'status' => 'required|in:delivered,rejected',
            'stage' => 'required|in:delivery',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        if ($request->status == 'delivered') {

            $order = orders::where('id', $request->order_id)->first();

            if ($order->payment_status == 'unpaid') {
                $order->update(['payment_status' => 'paid']);
            }

            $order->update(['status' => 'delivered']);

            $rider = restaurant_user_staff::where('id', $request->user_id)->first();

            $rider->update(['orders' => $rider->orders + 1]);
        } else {

            $order = orders::where('id', $request->order_id)->first();

            $order->update(['status' => 'cancelled']);
        }




        $orders_processing = orders_processing::create($request->all());


        $type = 'delivered_order';

        // $order = orders::with('orders_processing')->where('id', $orders_processing->order_id)->get();



        $order = orders::with([
            'orders_processing',
            'orders_processing.user'
        ])->find($orders_processing->order_id);



        $order = [$order];


        event(new notify_msg($order, $type));

        return response()->json([
            'success' => true,
            'message' => 'Order delivered successfully',
            'orders_processing' => $orders_processing,
        ]);
    }
}
