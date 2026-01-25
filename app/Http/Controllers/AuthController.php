<?php

namespace App\Http\Controllers;

use App\Models\qtap_admins;
use App\Models\qtap_affiliate;
use App\Models\clients_logs;
use App\Models\User;
use App\Models\qtap_clients_brunchs;
use App\Models\restaurant_user_staff;
use App\Models\qtap_clients;
use App\Models\restaurant_staff;
use App\Models\restaurant_users;
use App\Models\users_logs;
use App\Models\affiliate_log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use App\Helpers\SmsHelpre;

use Illuminate\Support\Str;

use App\Mail\OTPMail;

use Illuminate\Support\Facades\Mail;



use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // أضف هذا لضمان عمل Auth بشكل صحيح
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * تسجيل مستخدم جديد
     */

    public function send_phone_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15', // رقم الهاتف مطلوب
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات غير صالحة: ' . $validator->errors()->first(),
                'status' => false
            ], 422);
        }

        $phoneNumber = $request->phone_number;

        // Format phone number for Twilio (E.164 format)
        $formattedPhone = $this->formatEgyptianPhoneNumber($phoneNumber);

        if (!$formattedPhone) {
            return response()->json([
                'message' => 'رقم الهاتف غير صالح',
                'status' => false
            ], 422);
        }

        // إنشاء OTP
        $otpCode = rand(1000, 9999);

        // إرسال OTP عبر خدمة SMS
        try {
            $otpSent = SmsHelpre::sendMessage($formattedPhone, "كود التحقق الخاص بك هو: $otpCode");

            if ($otpSent) {
                $otpData = [
                    'otp_code' => $otpCode,
                    'phone_number' => $phoneNumber, // Store original format
                    'attempts' => 0,
                    'verified' => false,
                    'expires_at' => now()->addMinutes(15)
                ];

                $cacheKey = 'phone_otp_' . $phoneNumber;
                Cache::put($cacheKey, $otpData, now()->addMinutes(15));

                return response()->json([
                    'message' => 'تم إرسال كود التحقق بنجاح إلى رقم الهاتف',
                    'status' => true,
                    'data' => [
                        'phone_number' => $phoneNumber,
                        'expires_at' => $otpData['expires_at']->format('Y-m-d H:i:s')
                    ]
                ], 200);
            } else {
                return response()->json([
                    'message' => 'فشل في إرسال كود التحقق',
                    'status' => false
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إرسال كود التحقق: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }

    // دالة خاصة لتنسيق الأرقام المصرية
    private function formatEgyptianPhoneNumber($phone)
    {
        // تنظيف الرقم من أي رموز غير رقمية
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (empty($cleaned)) {
            return false;
        }

        // التحقق من الأرقام المصرية بأنواعها المختلفة
        $patterns = [
            // 01022966453 → +201022966453
            '/^01[0-2|5]{1}[0-9]{8}$/' => function($num) {
                return '+20' . $num;
            },

            // 1022966453 → +201022966453
            '/^1[0-2|5]{1}[0-9]{8}$/' => function($num) {
                return '+20' . $num;
            },

            // 201022966453 → +201022966453
            '/^20[0-9]{10}$/' => function($num) {
                return '+' . $num;
            },

            // +201022966453 → +201022966453 (مباشرة)
            '/^\+201[0-2|5]{1}[0-9]{8}$/' => function($num) {
                return $num;
            }
        ];

        foreach ($patterns as $pattern => $formatter) {
            if (preg_match($pattern, $cleaned)) {
                return $formatter($cleaned);
            }
        }

        return false;
    }


    public function verify_phone_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15',
            'otp_code' => 'required|numeric|digits:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات غير صالحة: ' . $validator->errors()->first(),
                'status' => false
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $otpCode = $request->otp_code;
        $cacheKey = 'phone_otp_' . $phoneNumber;

        // جلب بيانات OTP من الكاش
        $otpData = Cache::get($cacheKey);

        // التحقق من وجود OTP
        if (!$otpData) {
            return response()->json([
                'message' => 'لم يتم إرسال كود تحقق لهذا الرقم أو انتهت صلاحية الكود',
                'status' => false
            ], 404);
        }

        // التحقق من انتهاء الصلاحية
        if (now()->gt($otpData['expires_at'])) {
            Cache::forget($cacheKey); // حذف OTP من الكاش
            return response()->json([
                'message' => 'انتهت صلاحية كود التحقق',
                'status' => false
            ], 400);
        }

        // التحقق من عدد المحاولات
        if ($otpData['attempts'] >= 3) {
            Cache::forget($cacheKey); // حذف OTP من الكاش بعد 3 محاولات فاشلة
            return response()->json([
                'message' => 'تم تجاوز عدد المحاولات المسموح بها، يرجى طلب كود جديد',
                'status' => false
            ], 400);
        }

        // التحقق من صحة OTP
        if ($otpData['otp_code'] != $otpCode) {
            // زيادة عدد المحاولات الفاشلة
            $otpData['attempts']++;
            Cache::put($cacheKey, $otpData, $otpData['expires_at']);

            return response()->json([
                'message' => 'كود التحقق غير صحيح',
                'status' => false,
                'attempts_remaining' => 3 - $otpData['attempts']
            ], 400);
        }

        // إذا كان OTP صحيحاً - تحديث الحالة والاحتفاظ بالبيانات
        $otpData['verified'] = true;
        $otpData['verified_at'] = now();
        $otpData['expires_at'] = now()->addMinutes(30); // تمديد الصلاحية 30 دقيقة للاستخدام في التسجيل

        Cache::put($cacheKey, $otpData, $otpData['expires_at']);

        return response()->json([
            'message' => 'تم التحقق بنجاح',
            'status' => true,
            'data' => [
                'phone_number' => $phoneNumber,
                'verified' => true,
                'expires_at' => $otpData['expires_at']->format('Y-m-d H:i:s')
            ]
        ], 200);
    }


    public function resend_phone_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:15'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات غير صالحة: ' . $validator->errors()->first(),
                'status' => false
            ], 422);
        }

        $phoneNumber = $request->phone_number;
        $cacheKey = 'phone_otp_' . $phoneNumber;
        $formattedPhone = $this->formatEgyptianPhoneNumber($phoneNumber);
                if (!$formattedPhone) {
            return response()->json([
                'message' => 'رقم الهاتف غير صالح',
                'status' => false
            ], 422);
        }

        // إنشاء OTP

        // التحقق إذا كان هناك OTP نشط بالفعل
        $existingOtp = Cache::get($cacheKey);

        // إذا كان هناك OTP نشط ولم يتجاوز عدد المحاولات المسموح بها للإعادة
        if ($existingOtp && isset($existingOtp['resend_attempts']) && $existingOtp['resend_attempts'] >= 3) {
            return response()->json([
                'message' => 'تم تجاوز عدد محاولات إعادة الإرسال المسموح بها',
                'status' => false
            ], 400);
        }

        // إنشاء OTP جديد
       // $otpCode = 1111; // لأغراض الاختبار
        $otpCode = rand(1000, 9999);

        // إرسال OTP عبر خدمة SMS
        try {
            $otpSent = SmsHelpre::sendMessage($formattedPhone, "كود التحقق الخاص بك هو: $otpCode");


            if ($otpSent) {
                // تحديث بيانات OTP في الكاش
                $otpData = [
                    'otp_code' => $otpCode,
                    'phone_number' => $phoneNumber,
                    'attempts' => $existingOtp['attempts'] ?? 0, // الحفاظ على عدد محاولات التحقق
                    'resend_attempts' => ($existingOtp['resend_attempts'] ?? 0) + 1, // زيادة عدد محاولات الإعادة
                    'verified' => false,
                    'expires_at' => now()->addMinutes(15)
                ];

                Cache::put($cacheKey, $otpData, now()->addMinutes(15));

                return response()->json([
                    'message' => 'تم إعادة إرسال كود التحقق بنجاح',
                    'status' => true,
                    'data' => [
                        'phone_number' => $phoneNumber,
                        'expires_at' => $otpData['expires_at']->format('Y-m-d H:i:s'),
                        'resend_attempts' => $otpData['resend_attempts']
                    ]
                ], 200);
            } else {
                return response()->json([
                    'message' => 'فشل في إعادة إرسال كود التحقق',
                    'status' => false
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إعادة إرسال كود التحقق: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }



    public function check_phone_verification($phoneNumber)
    {
        $cacheKey = 'phone_otp_' . $phoneNumber;
        $otpData = Cache::get($cacheKey);

        if (!$otpData) {
            return [
                'verified' => false,
                'message' => 'لم يتم التحقق من رقم الهاتف'
            ];
        }

        if (!$otpData['verified'] || now()->gt($otpData['expires_at'])) {
            return [
                'verified' => false,
                'message' => 'لم يتم التحقق من رقم الهاتف أو انتهت صلاحية التحقق'
            ];
        }

        return [
            'verified' => true,
            'data' => $otpData
        ];
    }





    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات غير صالحة: ' . $validator->errors()->first(),
                'status' => false
            ], 422);
        }

        // تحديد نموذج المستخدم بناءً على النوع
        $userModel = match($request->user_type) {
            'qtap_admins' => qtap_admins::class,
            'qtap_affiliate' => qtap_affiliate::class,
            'qtap_clients' => qtap_clients::class,
        };

        // البحث عن المستخدم
        $user = $userModel::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
                'status' => false
            ], 404);
        }

        try {
            // إنشاء OTP جديد
            $newOTP = rand(100000, 999999);

            // تحديث OTP في قاعدة البيانات
            $user->update(['otp' => $newOTP]);

            // إرسال البريد الإلكتروني
            Mail::to($user->email)->send(new OTPMail($newOTP, 'كود التحقق الجديد'));

            return response()->json([
                'message' => 'تم إعادة إرسال كود التحقق بنجاح',
                'status' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إعادة إرسال الكود: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }

    public function register(Request $request)
    {

        qtap_affiliate::where('email', $request->email)->where('status', 'inactive')->delete();


        qtap_clients::where('email', $request->email)->where('status', 'inactive')->delete();


        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'mobile' => 'required|string|max:255|unique:qtap_clients|unique:qtap_admins|unique:qtap_affiliates',
            'birth_date' => 'required|date',
            'email' => 'required|string|email|max:255|unique:qtap_clients|unique:qtap_admins|unique:qtap_affiliates',
            'password' => 'required|string|min:1',
            'user_type' => 'required|in:qtap_admins,qtap_clients,qtap_affiliates',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'بعض البيانات غير مكتملة أو غير صحيحة.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phoneVerification = $this->check_phone_verification($request->mobile);

        if (!$phoneVerification['verified']) {
            return response()->json([
                'status' => 'error',
                'message' => 'يجب التحقق من رقم الهاتف قبل التسجيل',
                'phone_verification_required' => true
            ], 422);
        }

        $data = $request->all();

        if ($request->hasFile('img')) {
            $image = $request->file('img');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            $uploadPath = match ($request->user_type) {
                'qtap_admins' => 'uploads/qtap_admins',
                'qtap_clients' => 'uploads/qtap_clients',
                'qtap_affiliates' => 'uploads/qtap_affiliate',
                default => 'uploads/others',
            };

            $image->move(public_path($uploadPath), $imageName);
            $data['img'] = $uploadPath . '/' . $imageName;

            $data['img'] = 'storage/' . $data['img'];
        }

        try {
            if ($request->user_type === 'qtap_admins') {
                $user = qtap_admins::create($data);
            } elseif ($request->user_type === 'qtap_clients') {
                $user = qtap_clients::create($data);
            } elseif ($request->user_type === 'qtap_affiliates') {


                // $data['code'] = strtoupper(Str::random(8));


                $user = qtap_affiliate::create($data);
            } else {
                throw new \Exception("نوع المستخدم غير صالح.");
            }

            $token = JWTAuth::fromUser($user);
            $otp = 123456;
            //$otp = rand(100000, 999999);
          //  $user->update(['otp' => $otp]);
           // Mail::to($user->email)->send(new OTPMail($otp, 'تأكيد البريد الإلكتروني'));

            return response()->json([
                'status' => 'success',
                'message' => 'تم تسجيل المستخدم بنجاح.',
                // 'token' => $token,
                'user' => $user,
            ], 201);
        } catch (QueryException $e) {
            // التحقق إذا كان الخطأ هو انتهاك القيد الفريد
            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'رقم الهاتف أو البريد الإلكتروني مستخدم مسبقًا.',
                ], 409);  // استخدام كود الحالة 409 تعني تضارب
            }

            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ غير متوقع.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }


   /* public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'pin' => 'sometimes|string',
            'brunch_id' => 'sometimes|integer|exists:qtap_clients_brunchs,id',
            'user_type' => 'required|in:qtap_admins,qtap_clients,qtap_affiliates',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->user_type != 'qtap_clients') {
            $credentials = $request->only('email', 'password', 'user_type');
            $user = null;

            if ($token = Auth::guard('qtap_admins')->attempt($credentials)) {
                $user = Auth::guard('qtap_admins')->user();
            } elseif ($token = Auth::guard('qtap_affiliate')->attempt($credentials)) {
                $user = Auth::guard('qtap_affiliate')->user();

                if ($user->status !== 'active') {
                    return response()->json(['error' => 'User is not active'], 401);
                }

                affiliate_log::create([
                    'affiliate_id' => $user->id,
                    'status' => 'active',
                ]);
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } else {
            $user = restaurant_user_staff::where('pin', $request->pin)
                ->where('email', $request->email)
                ->where('brunch_id', $request->brunch_id)
                ->where('user_type', $request->user_type)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Unauthorized - Invalid pin or password or phone'], 401);
            }

            // Generate JWT token explicitly (avoids analyzer warning about guard->login() return type)
            $token = JWTAuth::fromUser($user);

            if ($user->role == 'delivery_rider' && $request['phone'] && $request['phone'] != $user->phone) {
                return response()->json(['error' => 'Unauthorized - Invalid phone'], 401);
            }

            users_logs::create([
                'user_id' => $user->id,
                'brunch_id' => $user->brunch_id,
                'status' => $user->status,
            ]);
        }

        $response = response()->json([
            'token' => $token,
            'user' => $user,
        ]);

        return $response->cookie(
            'qutap_auth',
            $token,
            60 * 24 * 7, // 7 أيام
            '/',
            null, // للعمل على جميع النطاقات المحلية
            false, // secure
            false, // httpOnly
            false,
            'lax'
        );
    }

    public function checkAuth(Request $request)
    {
        try {
            if (!$token = $request->cookie('qutap_auth')) {
                return response()->json(['authenticated' => false], 401);
            }

            // تحديد الجارد بناءً على نوع المستخدم
            $user = Auth::guard('restaurant_user_staff')->setToken($token)->user();

            if (!$user) {
                return response()->json([
                    'authenticated' => true,
                    'user' => false
                ]);
            }

            return response()->json([
                'authenticated' => true,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Authentication error'
            ], 401);
        }
    }

    public function logout(Request $request)
    {

        if (auth()->check()) {
            if (auth()->user()->user_type == 'qtap_clients') {
                users_logs::create([
                    'user_id' => auth()->user()->id,
                    'brunch_id' => auth()->user()->brunch_id,
                    'action' => 'inactive',
                ]);
            } elseif (auth()->user()->user_type == 'qtap_affiliates') {
                affiliate_log::create([
                    'user_id' => auth()->user()->id,
                    'action' => 'inactive',
                ]);
            }

            JWTAuth::invalidate(JWTAuth::getToken());
            Auth::logout();

            return response()->json(['success' => true, 'message' => 'Logout successful'])
                ->cookie('qutap_auth', null, -1);
        }

        return response()->json(['success' => false, 'message' => 'No user logged in']);
    }*/

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'pin' => 'sometimes|string',
            'brunch_id' => 'sometimes|integer|exists:qtap_clients_brunchs,id',
            'user_type' => 'required|in:qtap_admins,qtap_clients,qtap_affiliates',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->user_type != 'qtap_clients') {
            $credentials = $request->only('email', 'password', 'user_type');
            $user = null;

            if ($token = Auth::guard('qtap_admins')->attempt($credentials)) {
                $user = Auth::guard('qtap_admins')->user();
            } elseif ($token = Auth::guard('qtap_affiliate')->attempt($credentials)) {
                $user = Auth::guard('qtap_affiliate')->user();

                if ($user->status !== 'active') {
                    return response()->json(['error' => 'User is not active'], 401);
                }

                affiliate_log::create([
                    'affiliate_id' => $user->id,
                    'status' => 'active',
                ]);
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } else {
            $user = restaurant_user_staff::where('pin', $request->pin)
                ->where('email', $request->email)
                ->where('brunch_id', $request->brunch_id)
                ->where('user_type', $request->user_type)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Unauthorized - Invalid pin or password or phone'], 401);
            }

            // Generate JWT token explicitly (avoids analyzer warning about guard->login() return type)
            $token = JWTAuth::fromUser($user);

            if ($user->role == 'delivery_rider' && $request['phone'] && $request['phone'] != $user->phone) {
                return response()->json(['error' => 'Unauthorized - Invalid phone'], 401);
            }

            users_logs::create([
                'user_id' => $user->id,
                'brunch_id' => $user->brunch_id,
                'status' => 'active',
            ]);
        }

        $response = response()->json([
            'token' => $token,
            'user' => $user,
        ]);

        return $response->cookie(
            'qutap_auth',
            $token,
            60 * 24 * 7, // 7 أيام
            '/', // المسار: يعمل على جميع المسارات
            '.qutap.co', // النطاق الرئيسي (مع النقطة في البداية) ليشمل جميع النطاقات الفرعية
            true, // secure: يجب أن يكون true ليعمل على HTTPS فقط
            true, // httpOnly: للحماية من XSS
            false,
            'None' // SameSite: None ضروري للعمل عبر النطاقات
        );
    }

    public function checkAuth(Request $request)
    {
        try {
            if (!$token = $request->cookie('qutap_auth')) {
                return response()->json(['authenticated' => false], 401);
            }

            // محاولة المصادقة مع جميع الجاردز الممكنة
            $guards = ['qtap_admins', 'qtap_affiliate', 'restaurant_user_staff'];

            foreach ($guards as $guard) {
                Auth::shouldUse($guard); // تحديد الجارد الحالي
                if ($user = Auth::setToken($token)->user()) {
                    return response()->json([
                        'authenticated' => true,
                        'user' => $user,
                        'guard_used' => $guard // للإشارة فقط، يمكن إزالته لاحقًا
                    ]);
                }
            }

            return response()->json([
                'authenticated' => true, // لأن الكوكي موجود
                'user' => false,
                'message' => 'Token valid but no user found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Authentication error: ' . $e->getMessage()
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            if (auth()->user()->user_type == 'qtap_clients') {
                users_logs::create([
                    'user_id' => auth()->user()->id,
                    'brunch_id' => auth()->user()->brunch_id,
                    'action' => 'inactive',
                ]);
            } elseif (auth()->user()->user_type == 'qtap_affiliates') {
                affiliate_log::create([
                    'user_id' => auth()->user()->id,
                    'action' => 'inactive',
                ]);
            }

            JWTAuth::invalidate(JWTAuth::getToken());
            Auth::logout();

            return response()->json(['success' => true, 'message' => 'Logout successful'])
                // Must match the cookie attributes used in login() to reliably delete it in browsers
                ->cookie('qutap_auth', '', -1, '/', '.qutap.co', true, true, false, 'None');
        }

        return response()->json(['success' => false, 'message' => 'No user logged in']);
    }


















































































































































    //---------------------------API RESET PASSWORD & VERIFY EMAIL----------------------------------------

    public function sendOTP(Request $request)
    {

        $otp = rand(100000, 999999);

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }

        if ($request->user_type == 'qtap_admins') {
            $user = qtap_admins::where('email', $request->email)->first();
        } elseif ($request->user_type == 'qtap_affiliate') {
            $user = qtap_affiliate::where('email', $request->email)->first();
        } elseif ($request->user_type == 'qtap_clients') {
            $user = qtap_clients::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }



        $user->update(['otp' => $otp]);


        $data['otp'] = $otp;

        Mail::to($request->email)->send(new OTPMail($otp, 'test'));

        return response()->json([
            'message' => 'تم ارسال الكود بنجاح',
            'status' => true
        ]);
    }


    public function receiveOTP(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }

        $otp_user = $request->otp;

        if ($request->user_type == 'qtap_admins') {
            $user = qtap_admins::where('otp', $otp_user)->first();
        } elseif ($request->user_type == 'qtap_affiliate') {
            $user = qtap_affiliate::where('otp', $otp_user)->first();
        } elseif ($request->user_type == 'qtap_clients') {
            $user = qtap_clients::where('otp', $otp_user)->first();
        }

        if (!$user) {
            return response()->json([
                'message' => 'الكود غير صحيح',
                'status' => false
            ]);
        }


        return response()->json([
            'message' => 'تم التحقق من الكود بنجاح',
            'status' => true
        ]);
    }


    public function resetpassword(Request $request)
    {

        $validator = validator($request->all(), [
            'password' => 'sometimes|confirmed',
            'otp' => 'required',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }

        if ($request->user_type == 'qtap_admins') {

            $user = qtap_admins::where('otp', $request->otp)->first();
        } elseif ($request->user_type == 'qtap_affiliate') {

            $user = qtap_affiliate::where('otp', $request->otp)->first();
        } elseif ($request->user_type == 'qtap_clients') {

            $user = qtap_clients::where('otp', $request->otp)->first();
            $staff = restaurant_user_staff::where('user_id', $user->id)->get();

        }


        if (!$user) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }
        // dd($request->all());

        $user->update([

            'password' => Hash::make($request->password),
            'otp' => null
        ]);

        if ($request->user_type == 'qtap_clients') {
            foreach ($staff as $item) {
                $item->update([
                    'password' => Hash::make($request->password),
                ]);
            }
        }


        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح',
            'status' => true
        ]);
    }

    public function verfiy_email(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
            'user_type' => 'required|in:qtap_clients,qtap_affiliate,qtap_admins'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'حدث خطاء اثناء التسجيل: ' . $validator->errors(),
                'status' => false
            ]);
        }

        $otp_user = $request->otp;


        if ($request->user_type == 'qtap_admins') {
            $user = qtap_admins::where('otp', $otp_user)->first();
        } elseif ($request->user_type == 'qtap_affiliate') {
            $user = qtap_affiliate::where('otp', $otp_user)->first();
        } elseif ($request->user_type == 'qtap_clients') {
            $user = qtap_clients::where('otp', $otp_user)->first();
        }



        if (!$user) {
            return response()->json([
                'message' => 'الكود غير صحيح',
                'status' => false
            ]);
        }




        $user->update([
            'email_verified_at' => now(),
            'otp' => null
        ]);

        return response()->json([
            'message' => 'تم التحقق من الكود بنجاح',
            'status' => true
        ]);
    }
}
