<?php

namespace App\Http\Controllers;

use App\Models\setting_content;
use App\Models\setting_faq;
use App\Models\setting_features;
use App\Models\setting_our_clients;
use App\Models\setting_payment;
use App\Models\setting_videos;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function createSettingContent(Request $request)
    {
        try {
            $data = $request->validate([
                'img' => 'nullable|array',
                'img.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'titles' => 'required|array',
                'descriptions' => 'required|array',
                'features' => 'required|array',
            ]);

            $imagePaths = [];
            if ($request->hasFile('img')) {
                foreach ($request->file('img') as $image) {
                    $imagePath = $image->store('images/Content', 'public');
                    $imagePaths[] = 'storage/' . $imagePath;
                }
            }

            $data['img'] = json_encode($imagePaths);
            $data['titles'] = json_encode($data['titles']);
            $data['descriptions'] = json_encode($data['descriptions']);
            $data['features'] = json_encode($data['features']);

            // If the user has uploaded images, store them in the public folder
            $setting = setting_content::create($data);

            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    // Encode the image paths and other fields as JSON


    public function updateSettingContent(Request $request, $id)
    {
        try {
            // Create a new setting content


            // Return the new setting content as JSON
            $setting = setting_content::findOrFail($id);

            // If there's an error, return it as JSON
            if ($setting) {
                # code...
                $data = $request->validate([
                    'img' => 'nullable|array',
                    'img.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                    'titles' => 'nullable|array',
                    'descriptions' => 'nullable|array',
                    'features' => 'nullable|array',
                ]);



                $imagePaths = json_decode($setting->img, true) ?? [];
                if ($request->hasFile('img')) {
                    foreach ($imagePaths as $oldImagePath) {
                        if (file_exists(storage_path('app/public/' . $oldImagePath))) {
                            unlink(storage_path('app/public/' . $oldImagePath));
                        }
                    }

                    $imagePaths = [];
                    foreach ($request->file('img') as $image) {
                        $imagePath = $image->store('images/Content', 'public');
                        $imagePaths[] = 'storage/' .  $imagePath;
                    }
                }

                $data['img'] = json_encode($imagePaths);
                if (isset($data['titles'])) {
                    $data['titles'] = json_encode($data['titles']);
                }
                if (isset($data['descriptions'])) {
                    $data['descriptions'] = json_encode($data['descriptions']);
                }
                if (isset($data['features'])) {
                    $data['features'] = json_encode($data['features']);
                }

                $setting->update($data);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }



            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getSettingContent()
    {
        try {
            $settings = setting_content::all();

            if ($settings) {
                return response()->json($settings);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingContent($id)
    {
        try {
            $setting = setting_content::findOrFail($id);


            if ($setting) {
                # code...
                // Delete image if exists
                if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                    unlink(storage_path('app/public/' . $setting->img));
                }
                $setting->delete();
                return response()->json(['message' => 'Deleted successfully']);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingFaq(Request $request)
    {
        try {
            $data = $request->validate([
                'question' => 'required|array',
                'answer' => 'required|array',
            ]);

            if (count($data['question']) !== count($data['answer'])) {
                return response()->json([
                    'error' => 'The number of questions must match the number of answers.',
                ], 422);
            }

            $data['question'] = json_encode($data['question']);
            $data['answer'] = json_encode($data['answer']);




            $Q =  setting_faq::create($data);

            return response()->json([
                'message' => 'FAQs created successfully',
                'data' => $Q,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateSettingFaq(Request $request, $id)
    {
        try {


            $setting = setting_faq::findOrFail($id);

            if ($setting) {

                $data = $request->validate([
                    'question' => 'required|array',
                    'answer' => 'required|array',
                ]);

                if (count($data['question']) !== count($data['answer'])) {
                    return response()->json([
                        'error' => 'The number of questions must match the number of answers.',
                    ], 422);
                }

                $data['question'] = json_encode($data['question']);
                $data['answer'] = json_encode($data['answer']);


                $setting->update($data);
                return response()->json($setting);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingFaq()
    {
        try {
            $settings = setting_faq::all();

            if ($settings) {
                # code...


                return response()->json($settings);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingFaq($id)
    {
        try {
            $setting = setting_faq::findOrFail($id);

            if ($setting) {
                # code...
                $setting->delete();
                return response()->json(['message' => 'Deleted successfully']);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingFeatures(Request $request)
    {
        try {
            $data = $request->validate([
                'img' => 'nullable|array',
                'titles' => 'required|array',
                'descriptions' => 'required|array',
                'features' => 'required|array',
            ]);

            $imagePaths = [];
            if ($request->hasFile('img')) {
                foreach ($request->file('img') as $image) {
                    $imagePath = $image->store('images/Content', 'public');
                    $imagePaths[] =  'storage/' . $imagePath;
                }
            }

            $data['img'] = json_encode($imagePaths);


            $data['titles'] = json_encode($data['titles']);
            $data['descriptions'] = json_encode($data['descriptions']);
            $data['features'] = json_encode($data['features']);

            $setting = setting_features::create($data);
            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateSettingFeatures(Request $request, $id)
    {
        try {

            $setting = setting_features::find($id);


            if ($setting) {
                $data = $request->validate([
                    'img' => 'nullable|array',
                    'titles' => 'required|array',
                    'descriptions' => 'required|array',
                    'features' => 'required|array',
                ]);

                $imagePaths = [];
                if ($request->hasFile('img')) {
                    foreach ($request->file('img') as $image) {
                        $imagePath = $image->store('images/Content', 'public');
                        $imagePaths[] =  'storage/' .  $imagePath;
                    }
                }

                $data['img'] = json_encode($imagePaths);


                $data['titles'] = json_encode($data['titles']);
                $data['descriptions'] = json_encode($data['descriptions']);
                $data['features'] = json_encode($data['features']);

                $setting->update($data);
                return response()->json($setting);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingFeatures()
    {
        try {
            $settings = setting_features::all();

            if ($settings) {
                return response()->json($settings);
                # code...
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingFeatures($id)
    {
        try {
            $setting = setting_features::findOrFail($id);

            if ($setting) {
                # code...

                // Delete image if exists
                if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                    unlink(storage_path('app/public/' . $setting->img));
                }
                $setting->delete();
            } else {
                return response()->json(['error' => 'Setting not found']);
            }

            return response()->json(['message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingOurClients(Request $request)
    {
        try {
            $data = $request->validate([
                'img' => 'required|array',
                'title' => 'nullable|array',
            ]);

            $imagePaths = [];
            if ($request->hasFile('img')) {
                foreach ($request->file('img') as $image) {
                    $imagePath = $image->store('images/Content', 'public');
                    $imagePaths[] =  'storage/' .  $imagePath;
                }
            }

            $data['img'] = json_encode($imagePaths);

            if (isset($data['title'])) {
                $data['title'] = json_encode($data['title']);
            }

            $setting = setting_our_clients::create($data);

            return response()->json($setting, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function updateSettingOurClients(Request $request, $id)
    {
        try {

            $setting = setting_our_clients::findOrFail($id);


            if ($setting) {

                $data = $request->validate([
                    'img' => 'required|array',
                    'title' => 'nullable|array',
                ]);

                $imagePaths = [];
                if ($request->hasFile('img')) {
                    foreach ($request->file('img') as $image) {
                        $imagePath = $image->store('images/Content', 'public');
                        $imagePaths[] =  'storage/' .  $imagePath;
                    }
                }

                $data['img'] = json_encode($imagePaths);


                $data['title'] = json_encode($data['title']);

                $setting->update($data);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }

            return response()->json($setting);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingOurClients()
    {
        try {
            $settings = setting_our_clients::all();


            if ($settings) {
                # code...
                return response()->json($settings);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingOurClients($id)
    {
        try {
            $setting = setting_our_clients::findOrFail($id);

            if ($setting) {
                # code...
                // Delete image if exists
                if ($setting->img && file_exists(storage_path('app/public/' . $setting->img))) {
                    unlink(storage_path('app/public/' . $setting->img));
                }
                $setting->delete();
                return response()->json(['message' => 'Deleted successfully']);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingPayment(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'API_KEY' => 'required|string',
                'IFRAME_ID' => 'required|string',
                'INTEGRATION_ID' => 'required|string',
                'HMAC' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // حذف جميع السجلات السابقة
            setting_payment::query()->delete();

            // إنشاء سجل جديد
            $setting = setting_payment::create($data);

            setting_payment::onlyTrashed()->forceDelete();

            return response()->json($setting, 201);
        } catch (\Exception $e) {
            // استعادة أي سجلات محذوفة عند حدوث خطأ
            setting_payment::onlyTrashed()->restore();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function updateSettingPayment(Request $request, $id)
    {
        try {

            $setting = setting_payment::findOrFail($id);

            if ($setting) {
                # code...
                $data = $request->validate([
                    'API_KEY' => 'required|string',
                    'IFRAME_ID' => 'required|string',
                    'INTEGRATION_ID' => 'required|string',
                    'HMAC' => 'required|string',
                ]);

                $setting->update($data);
                return response()->json($setting);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingPayment()
    {
        try {
            $settings = setting_payment::all();
            if ($settings) {
                # code...
                return response()->json($settings);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingPayment($id)
    {
        try {

            $setting = setting_payment::findOrFail($id);

            if ($setting) {
                # code...
                $setting->delete();
                return response()->json(['message' => 'Deleted successfully']);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function createSettingVideos(Request $request)
    {
        try {


            $data = $request->validate([
                'video' => 'required|array|max:255',
            ]);

            $settings = []; // استخدمنا array بدلاً من سلسلة نصية
            foreach ($data['video'] as $video) {
                $data['video'] = json_encode($video);

                // إنشاء عنصر جديد وإضافته إلى المصفوفة
                $settings[] = setting_videos::create($data);
            }




            return response()->json($settings, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateSettingVideos(Request $request, $id)
    {
        try {
            $setting = setting_videos::findOrFail($id);


            if ($setting) {

                $data = $request->validate([
                    'video' => 'nullable|string',
                ]);

                $setting->update($data);
                return response()->json($setting);
                # code...
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getSettingVideos()
    {
        try {
            $settings = setting_videos::all();

            if ($settings) {
                # code...
                return response()->json($settings);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function deleteSettingVideos($id)
    {
        try {
            $setting = setting_videos::findOrFail($id);

            if ($setting) {
                # code...


                $setting->delete();
                return response()->json(['message' => 'Deleted successfully']);
            } else {
                return response()->json(['error' => 'Setting not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}
