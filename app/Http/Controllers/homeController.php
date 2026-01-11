<?php

namespace App\Http\Controllers;

use App\Models\setting_features;
use App\Models\setting_content;
use App\Models\setting_faq;
use App\Models\setting_our_clients;
use App\Models\setting_videos;
use App\Models\pricing;
use App\Models\qtap_affiliate;
use App\Models\affiliate_clicks;
use App\Models\feedback;

use Illuminate\Http\Request;

class homeController extends Controller
{
    public function index()
    {

        $features = setting_features::all();
        $content = setting_content::all();
        $faq = setting_faq::all();
        $clients = setting_our_clients::all();
        $videos = setting_videos::all();
        $pricing = pricing::where('is_active', 'active')->get();
        $feedback = feedback::with('client')->where('publish', 'yes')->get();

        return response([
            'features' => $features,
            'content' => $content,
            'faq' => $faq,
            'clients' => $clients,
            'pricing' => $pricing,
            'feedback' => $feedback,
            'videos' => $videos,
        ]);
    }





    public function home_affiliate($affiliate_code)
    {



        $affiliate = qtap_affiliate::where('code', $affiliate_code)->first();

        if (!$affiliate) {
            return response()->json([
                'error' => 'code affiliate not found'
            ], 404);
        }

        affiliate_clicks::create([
            'affiliate_code' => $affiliate_code
        ]);


   

        $features = setting_features::all();
        $content = setting_content::all();
        $faq = setting_faq::all();
        $clients = setting_our_clients::all();
        $videos = setting_videos::all();
        $pricing = pricing::where('is_active', 'active')->get();
        $feedback = feedback::with('client')->where('publish', 'yes')->get();

        return response([
            'affiliate_code' => $affiliate_code,
            'features' => $features,
            'content' => $content,
            'faq' => $faq,
            'clients' => $clients,
            'pricing' => $pricing,
            'feedback' => $feedback,
            'videos' => $videos,
        ]);
    }
}
