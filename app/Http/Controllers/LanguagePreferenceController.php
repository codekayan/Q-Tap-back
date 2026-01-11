<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\LanguagePreference;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth; // أضف هذا لضمان عمل Auth بشكل صحيح

class LanguagePreferenceController extends Controller
{


    public function storeLanguagePreference(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|max:10|in:ar,en,fr',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // استخراج التوكن من الطلب
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'التوكن مطلوب'
                ], 401);
            }

            // الحصول على المستخدم من التوكن
            $user = $this->getUserFromToken($token);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المستخدم غير موجود أو التوكن غير صالح'
                ], 404);
            }

            $language = $request->language;

            // حفظ أو تحديث تفضيل اللغة
            $user->languagePreference()->updateOrCreate(
                [
                    'languageable_id' => $user->id,
                    'languageable_type' => get_class($user)
                ],
                [
                    'language' => $language,
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'تم حفظ تفضيلات اللغة بنجاح',
                'data' => [
                    'user_id' => $user->id,
                    'user_type' => get_class($user),
                    'language' => $language,
                ]
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'توكن غير صالح',
                'error' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء حفظ التفضيلات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getLanguagePreference(Request $request)
    {
        try {
            // استخراج التوكن من الطلب
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'التوكن مطلوب'
                ], 401);
            }

            // الحصول على المستخدم من التوكن
            $user = $this->getUserFromToken($token);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المستخدم غير موجود أو التوكن غير صالح'
                ], 404);
            }

            // الحصول على تفضيل اللغة إذا كان موجوداً
            $languagePreference = $user->languagePreference;

            if (!$languagePreference) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'لم يتم تعيين تفضيل لغة بعد',
                    'data' => [
                        'language' => null
                    ]
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم استرجاع تفضيل اللغة بنجاح',
                'data' => [
                    'user_id' => $user->id,
                    'user_type' => get_class($user),
                    'language' => $languagePreference->language,
                ]
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'توكن غير صالح',
                'error' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء استرجاع التفضيلات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getUserFromToken($token)
    {
        $guards = ['qtap_admins', 'qtap_affiliate', 'restaurant_user_staff', 'qtap_clients'];

        foreach ($guards as $guard) {
            Auth::shouldUse($guard);
            if ($user = Auth::setToken($token)->user()) {
                return $user;
            }
        }

        return null;
    }

}
