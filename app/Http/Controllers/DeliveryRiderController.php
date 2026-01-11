<?php

namespace App\Http\Controllers;

use App\Models\delivery_area;
use App\Models\qtap_clients_brunchs;
use App\Models\orders_processing;
use App\Models\restaurant_user_staff;
use App\Models\delivery_rider;
use App\Models\orders;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
// call hash
use Illuminate\Support\Facades\Hash;

use Carbon\Carbon;


use Illuminate\Validation\Rule;


use Illuminate\Support\Facades\DB;


class DeliveryRiderController extends Controller
{

    public function index()
    {
        $delivery_riders = restaurant_user_staff::where('role', 'delivery_rider')->get();
        return response()->json([
            'success' => true,
            'delivery_riders' => $delivery_riders
        ]);
    }


    public function get_delivery_available(){
        $brunch_id = Auth::user()->brunch_id;

        $delivery_riders = restaurant_user_staff::where('status_rider', 'Available')->where('brunch_id', $brunch_id)->where('role', 'delivery_rider')->get();
        return response()->json([
            'success' => true,
            'delivery_riders' => $delivery_riders
        ]);
    }



    public function store(Request $request)
    {
        DB::beginTransaction(); // بدء المعاملة

        try {


            $user = auth()->user();
            $request['user_id'] = $user->user_id;
            $request['password'] = $user->password;
            $request['email'] = $user->email;
            $request['role'] = 'delivery_rider';

            $validate = Validator::make($request->all(), [
                'brunch_id' => 'required|integer|exists:qtap_clients_brunchs,id',
                'delivery_areas_id' => 'required|integer|exists:delivery_areas,id',
                'name' => 'required|string|max:255|unique:restaurant_user_staffs,name',
                'phone' => 'required|string',
                'pin' => 'required|string|min:6|max:6|unique:restaurant_user_staffs,pin',
                'password' => 'required|string',
                'email' => 'required|string|email',
                'role' => 'required|in:delivery_rider',
                'user_id' => 'required|integer|exists:qtap_clients,id',
                'status_rider' => 'required|in:Available,Busy',
                'status' => 'sometimes|in:active,inactive',


            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors()
                ]);
            }

            $data = $request->all();

            $brunch_id = qtap_clients_brunchs::find($data['brunch_id']);
            if (!$brunch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'brunch not found'
                ]);
            }

            $delivery_areas_id = delivery_area::find($data['delivery_areas_id']);
            if (!$delivery_areas_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_area not found'
                ]);
            }

            $restaurant_staff = restaurant_user_staff::create($request->all());

            DB::commit(); // تأكيد التغييرات في قاعدة البيانات

            return response()->json([
                'success' => true,
                'restaurant_staff' => $restaurant_staff
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // التراجع عن جميع العمليات إذا حدث خطأ

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function update(Request $request )
    {

        $user = auth()->user();
        $id = $user->id;
        DB::beginTransaction();

        try {

            $restaurant_staff = restaurant_user_staff::find($id);


            if (!$restaurant_staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_rider not found'
                ]);
            }

            $request['email'] = $restaurant_staff->email;


            $validate = Validator::make($request->all(), [
                'brunch_id' => 'sometimes|integer|exists:qtap_clients_brunchs,id',
                'delivery_areas_id' => 'sometimes|integer|exists:delivery_areas,id',

                'name' => ['sometimes', 'string', Rule::unique('restaurant_user_staffs', 'name')->ignore($restaurant_staff->id)],


                'phone' => 'sometimes|string',
                'pin' => [
                    'sometimes',
                    'string',
                    'min:6',
                    'max:6',
                    Rule::unique('restaurant_user_staffs', 'pin')->ignore($restaurant_staff->id),
                ],
                'password' => 'sometimes|string',
                'email' => 'sometimes|string|email',
                'role' => 'sometimes|in:delivery_rider',
                'status_rider' => 'sometimes|in:Available,Busy',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors()
                ]);
            }

            $data = $request->all();




            // تحديث delivery_rider
            $restaurant_staff->update($data);



            DB::commit();

            return response()->json([
                'success' => true,
                'restaurant_staff' => $restaurant_staff
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }



    public function destroy($id)
    {
        try {

            $delivery_rider = restaurant_user_staff::find($id);
            if (!$delivery_rider) {
                return response()->json([
                    'success' => false,
                    'message' => 'delivery_rider not found'
                ]);
            }
            $delivery_rider->delete();
            return response()->json([
                'success' => true,
                'message' => 'delivery_rider deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function Total_Delivered_Orders($id)
    {
        $user = $id;

        $Total_Delivered_Orders = orders_processing::where('user_id', $user)->where('status', 'delivered')->count();

        return response()->json([
            'success' => true,
            'Total_Delivered_Orders' => $Total_Delivered_Orders,
        ]);
    }



    public function Daily_Delivered_Orders($id)
    {
        $userId = $id;

        $today = Carbon::today();

        $totalDeliveredOrders = orders_processing::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereDate('updated_at', $today)
            ->count();

        return response()->json([
            'success' => true,
            'total_delivered_orders' => $totalDeliveredOrders,
        ]);
    }


    public function Daily_Cancaled_Orders($id)
    {
        $userId = $id;

        $today = Carbon::today();

        $totalDeliveredOrders = orders_processing::where('user_id', $userId)
            ->where('status', 'cancelled')
            ->whereDate('updated_at', $today)
            ->count();

        return response()->json([
            'success' => true,
            'total_Cancaled_delivered_orders' => $totalDeliveredOrders,
        ]);
    }


    public function orders(Request $request)
    {
        $user = $request->user_id;
        if ($user) {
            $orders = orders::with(
                [

                    'orders_processing' => function ($query) use ($user) {
                        $query->where('user_id', $user);
                    }
                ]

            )->where('user_id', $user)->where('status', 'delivered')->get();

        } else {

            $orders = orders::with('orders_processing')->get();
        }

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }
}
