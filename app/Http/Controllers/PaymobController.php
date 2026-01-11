<?php

namespace App\Http\Controllers;

use App\Models\qtap_clients;
use App\Models\Revenue;
use App\Models\setting_payment;
use App\Models\Campaigns;
use App\Models\qtap_clients_brunchs;
use App\Models\services_client;
use App\Models\revenue_restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\log;

use App\Mail\active_account;
use App\Models\affiliate_Revenues;
use App\Models\orders;
use App\Models\qtap_affiliate;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\DB;


class PaymobController extends Controller
{



    //return the data from paymob and we show the full response and we checked if hmac is correct means successfull payment

    public function callback(Request $request)
    {
        // dd($request->all());
        //this call back function its return the data from paymob and we show the full response and we checked if hmac is correct means successfull payment

        $data = $request->all();


        $payment_data = setting_payment::first();

        if (!$payment_data) {
            return response()->json(['error' => 'Payment data not found'], 404);
        }
        $PAYMOB_HMAC = $payment_data->HMAC;


        ksort($data);
        $hmac = $data['hmac'];
        $array = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order',
            'owner',
            'pending',
            'source_data_pan',
            'source_data_sub_type',
            'source_data_type',
            'success',
        ];
        $connectedString = '';
        foreach ($data as $key => $element) {
            if (in_array($key, $array)) {
                $connectedString .= $element;
            }
        }
        $secret = $PAYMOB_HMAC;
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ($hased == $hmac) {

            $status = $data['success'];


            $order_id = $data['order'];

            $new_client = qtap_clients::where('order_id', $order_id)->first();


            $new_client->update([
                'status' => 'active'
            ]);


            $affiliate_code = null;

            $brunshs = qtap_clients_brunchs::where('order_id', $order_id)->where('status', 'inactive')->get();

            foreach ($brunshs as $brunsh) {
                $brunsh->update([
                    'status' => 'active'
                ]);

                $affiliate_code = $brunsh->affiliate_code;
            }




            if ($affiliate_code) {

                $campaign_order = affiliate_Revenues::where('order_id', $order_id)->first();

                $affiliate_id = qtap_affiliate::where('code', $affiliate_code)->first();

                if ($campaign_order) {

                $campaign = Campaigns::find($campaign_order->campaign_id);



                    $value = ($data['amount_cents'] / 100) * ($campaign->commission / 100);



                    affiliate_Revenues::create([
                        'affiliate_id' => $affiliate_id->id,
                        'order_id' => $order_id,
                        'amount' => $value,
                        'value_order' => ($data['amount_cents'] / 100),
                        'commission' => $campaign->commission,
                        'campaign_id' => $campaign_order->campaign_id,
                        'affiliate_code' => $affiliate_code
                    ]);

                } else {

                    $value = ($data['amount_cents'] / 100) * (10 / 100);

                    affiliate_Revenues::create([
                        'affiliate_id' => $affiliate_id->id,
                        'order_id' => $order_id,
                        'amount' => $value,
                        'value_order' => ($data['amount_cents'] / 100),
                        'commission' => 10,
                        'affiliate_code' => $affiliate_code
                    ]);

                }
            }

            Revenue::create([
                'order_id' => $order_id,
                'client_id' => $new_client->id,
                'value' => ($data['amount_cents'] / 100)
            ]);


            if ($status == "true") {

                Mail::to($new_client->email)->send(new active_account('account activated'));

                return redirect('thankyou')->with('success', 'Payment Successfull ... you can login now');
            } else {

                return redirect('/thankyou')->with('error', 'Something Went Wrong Please Try Again' . $data);
            }
        } else {
            return redirect('/thankyou')->with('error', 'Something Went Wrong Please Try Again' . $data);
        }
    }


    public function callback_order(Request $request)
    {

        $data = $request->all();


        $payment_data = setting_payment::first();
        if (!$payment_data) {
            return response()->json(['error' => 'Payment data not found'], 404);
        }
        $PAYMOB_HMAC = $payment_data->HMAC;


        ksort($data);
        $hmac = $data['hmac'];
        $array = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order',
            'owner',
            'pending',
            'source_data_pan',
            'source_data_sub_type',
            'source_data_type',
            'success',
        ];
        $connectedString = '';
        foreach ($data as $key => $element) {
            if (in_array($key, $array)) {
                $connectedString .= $element;
            }
        }
        $secret = $PAYMOB_HMAC;
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ($hased == $hmac) {

            $status = $data['success'];


            $order_id = $data['order'];

            $order = orders::where('reference_number', $order_id)->first();



            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);


            if ($status == "true") {

                revenue_restaurant::create([

                    'order_id' => $order->id,
                    'brunch_id' => $order->brunch_id,
                    'ref_number' => $order->reference_number,
                    'value' => ($data['amount_cents'] / 100)
                ]);





                return redirect('thankyou')->with('success', 'Payment Successfull ... you can login now');
            } else {

                return redirect('/thankyou')->with('error', 'Something Went Wrong Please Try Again' . $data);
            }
        } else {
            return redirect('/thankyou')->with('error', 'Something Went Wrong Please Try Again' . $data);
        }
    }


    public function getcallback()
    {
        // return redirect()->route('thankyou')->with('success', 'Payment Successfull ... you can login now');
        return redirect()->route('thankyou')->with('error', 'Something Went Wrong Please Try Again');
    }




    public function processPayment(array $orderData, array $userData)
    {
        try {

            $payment_data = setting_payment::first();


            if (!$payment_data) {
                return [
                    'status' => 'error',
                    'message' => 'Payment data not found'
                ];
            }


            $API_KEY = $payment_data->API_KEY;
            $IFRAME_ID = $payment_data->IFRAME_ID;
            $INTEGRATION_ID = $payment_data->INTEGRATION_ID;
            $PAYMOB_HMAC = $payment_data->HMAC;




            $new_client = qtap_clients::find($userData['user_id']);

            // Step 1: Get API Token from Paymob
            $tokenResponse = Http::post('https://accept.paymob.com/api/auth/tokens', [
                'api_key' => $API_KEY
            ]);



            if (!$tokenResponse->successful()) {

                $new_client->forceDelete();

                Log::error('Paymob Token Request Failed', [
                    'status_code' => $tokenResponse->status(),
                    'response_body' => $tokenResponse->body()
                ]);
                throw new \Exception("Failed to retrieve authentication token.");
            }




            $authToken = $tokenResponse->object()->token;

            // Step 2: Create Order on Paymob
            $orderPayload = [
                "auth_token" => $authToken,
                "delivery_needed" => "false",
                "amount_cents" => intval($orderData['total']) * 100,
                "currency" => $orderData['currency'] ?? "EGP",
                "items" => $orderData['items'] ?? []
            ];



            $orderResponse = Http::post('https://accept.paymob.com/api/ecommerce/orders', $orderPayload);


            if (!$orderResponse->successful()) {
                $new_client->forceDelete();

                Log::error('Paymob Order Request Failed', [
                    'status_code' => $orderResponse->status(),
                    'response_body' => $orderResponse->body()
                ]);
                throw new \Exception("Failed to create order." . $orderResponse->body());
            }


            $order = $orderResponse->object();

            $new_client->update([

                'order_id' => $order->id

            ]);


            $brunshs_inactive = qtap_clients_brunchs::where('client_id', $userData['user_id'])->where('status', 'inactive')->get();

            foreach ($brunshs_inactive as $brunsh) {
                $brunsh->update([

                    'order_id' => $order->id,
                    'affiliate_code' => $userData['affiliate_code'] ?? null
                ]);
            }


            if ($userData['affiliate_code'] != null) {
                $campaigns = Campaigns::where('status', 'active')->withCount('affiliateRevenues')->get();


                // dd($campaigns);

                foreach ($campaigns as $campaign) {
                    if ($campaign->affiliate_revenues_count < $campaign->limit) {
                        affiliate_Revenues::create([
                            'campaign_id' => $campaign->id,
                            'order_id' => $order->id,
                            'affiliate_code' => $userData['affiliate_code'],
                        ]);

                        break; // الخروج من اللوب بعد إنشاء سجل جديد
                    }
                }
            }



            // Step 3: Generate Payment Token
            $billingData = [
                "apartment" => $userData['apartment'] ?? 'N/A',
                "email" => $userData['email'],
                "floor" => $userData['floor'] ?? 'N/A',
                "first_name" => $userData['first_name'],
                "street" => $userData['street'] ?? 'N/A',
                "building" => $userData['building'] ?? 'N/A',
                "phone_number" => $userData['phone_number'],
                "shipping_method" => $userData['shipping_method'] ?? 'N/A',
                "postal_code" => $userData['postal_code'] ?? 'N/A',
                "city" => $userData['city'] ?? 'N/A',
                "country" => $userData['country'] ?? 'N/A',
                "last_name" => $userData['last_name'],
                "state" => $userData['state'] ?? 'N/A'
            ];

            $paymentPayload = [
                "auth_token" => $authToken,
                "amount_cents" => intval($orderData['total']) * 100,
                "expiration" => 3600,
                "order_id" => $order->id,
                "billing_data" => $billingData,
                "currency" => $orderData['currency'] ?? "EGP",
                "integration_id" => $INTEGRATION_ID
            ];

            $paymentResponse = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', $paymentPayload);


            if (!$paymentResponse->successful()) {
                $new_client->forceDelete();



                Log::error('Paymob Payment Key Request Failed', [
                    'status_code' => $paymentResponse->status(),
                    'response_body' => $paymentResponse->body()
                ]);
                throw new \Exception("Failed to generate payment token.");
            }

            $paymentToken = $paymentResponse->object()->token;
            $paymentUrl = 'https://accept.paymob.com/api/acceptance/iframes/' .  $IFRAME_ID . '?payment_token=' . $paymentToken;


            // Return payment result or redirect
            return ['status' => 'success', 'payment_url' => $paymentUrl];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }



    public function processPayment_orders(array $orderData, array $userData)
    {
        try {

            $payment_data = setting_payment::first();

            if (!$payment_data) {
                return response()->json(['error' => 'Payment data not found'], 404);
            }

            $API_KEY = $payment_data->API_KEY;
            $IFRAME_ID = $payment_data->IFRAME_ID;
            $INTEGRATION_ID = $payment_data->INTEGRATION_ID;
            $PAYMOB_HMAC = $payment_data->HMAC;





            $client_order = orders::find($userData['order_id']);

            // Step 1: Get API Token from Paymob
            $tokenResponse = Http::post('https://accept.paymob.com/api/auth/tokens', [
                'api_key' => $API_KEY
            ]);



            if (!$tokenResponse->successful()) {


                Log::error('Paymob Token Request Failed', [
                    'status_code' => $tokenResponse->status(),
                    'response_body' => $tokenResponse->body()
                ]);
                throw new \Exception("Failed to retrieve authentication token.");
            }




            $authToken = $tokenResponse->object()->token;

            // Step 2: Create Order on Paymob
            $orderPayload = [
                "auth_token" => $authToken,
                "delivery_needed" => "false",
                "amount_cents" => intval($orderData['total']) * 100,
                "currency" => $orderData['currency'] ?? "EGP",
                "items" => $orderData['items'] ?? []
            ];



            $orderResponse = Http::post('https://accept.paymob.com/api/ecommerce/orders', $orderPayload);

            if (!$orderResponse->successful()) {

                Log::error('Paymob Order Request Failed', [
                    'status_code' => $orderResponse->status(),
                    'response_body' => $orderResponse->body()
                ]);
                throw new \Exception("Failed to create order." . $orderResponse->body());
            }


            $order = $orderResponse->object();

            $client_order->update([

                'reference_number' => $order->id

            ]);







            // Step 3: Generate Payment Token
            $billingData = [
                "apartment" => $userData['apartment'] ?? 'N/A',
                "email" => $userData['email'],
                "floor" => $userData['floor'] ?? 'N/A',
                "first_name" => $userData['first_name'],
                "street" => $userData['street'] ?? 'N/A',
                "building" => $userData['building'] ?? 'N/A',
                "phone_number" => $userData['phone_number'],
                "shipping_method" => $userData['shipping_method'] ?? 'N/A',
                "postal_code" => $userData['postal_code'] ?? 'N/A',
                "city" => $userData['city'] ?? 'N/A',
                "country" => $userData['country'] ?? 'N/A',
                "last_name" => $userData['last_name'],
                "state" => $userData['state'] ?? 'N/A'
            ];

            $paymentPayload = [
                "auth_token" => $authToken,
                "amount_cents" => intval($orderData['total']) * 100,
                "expiration" => 3600,
                "order_id" => $order->id,
                "billing_data" => $billingData,
                "currency" => $orderData['currency'] ?? "EGP",
                "integration_id" => $INTEGRATION_ID
            ];

            $paymentResponse = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', $paymentPayload);

            if (!$paymentResponse->successful()) {



                Log::error('Paymob Payment Key Request Failed', [
                    'status_code' => $paymentResponse->status(),
                    'response_body' => $paymentResponse->body()
                ]);
                throw new \Exception("Failed to generate payment token.");
            }

            $paymentToken = $paymentResponse->object()->token;
            $paymentUrl = 'https://accept.paymob.com/api/acceptance/iframes/' .  $IFRAME_ID . '?payment_token=' . $paymentToken;

            // Return payment result or redirect
            return ['status' => 'success', 'payment_url' => $paymentUrl];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
