<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class qtap_clients_brunchs extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'contact_info_id',
        'currency_id',
        //'pricing_id',
        'discount_id',
        'business_name',
        'business_country',
        'business_city',
        'latitude',
        'longitude',
        'business_format',
        'payment_method',
        'menu_design',
        'default_mode',
        'payment_time',
        'call_waiter',
        'status',
        'order_id',
        'affiliate_code',
    ];

    public function client(){

        return $this->belongsTo(qtap_clients::class , 'client_id' , 'id');
    }


    public function role(){

        return $this->hasMany(role::class ,  'brunch_id', 'id')->select('id' , 'name' , 'brunch_id');
    }

public function pricing()
{
    return $this->belongsTo(pricing::class, 'pricing_id', 'id')
        ->withDefault(function() {
            // إنشاء نموذج pricing فارغ بقيم افتراضية آمنة
            $emptyPricing = new pricing();

            // تعيين قيم افتراضية لجميع الحقول المهمة
            $emptyPricing->id = 0; // قيمة غير موجودة في قاعدة البيانات
            $emptyPricing->name = 'No Active Subscription';
            $emptyPricing->orders_limit = 0;
            $emptyPricing->monthly_price = 0;
            $emptyPricing->yearly_price = 0;
            $emptyPricing->is_active = 'inactive';

            // محاولة جلب اشتراك العميل النشط
            try {
                if ($this->client &&
                    $activeSubscription = $this->client->activeSubscription) {
                    return $activeSubscription->pricing ?? $emptyPricing;
                }
            } catch (\Exception $e) {
                // تسجيل الخطأ إذا لزم الأمر
                Log::error('Error fetching active subscription: ' . $e->getMessage());
            }

            return $emptyPricing;
        });
}

// خاصية مساعدة محسنة للحصول على الاشتراك النشط
public function getActiveSubscriptionAttribute()
{
    try {
        if ($this->client) {
            return $this->client->clientPricings()
                ->where('status', 'active')
                ->latest()
                ->first();
        }
    } catch (\Exception $e) {
        Log::error('Error in getActiveSubscriptionAttribute: ' . $e->getMessage());
    }

    return null;
}

 /*   public function pricing(){
        return $this->belongsTo(pricing::class , 'pricing_id' , 'id');
    }*/


    public function workschedule(){

        return $this->hasMany(workschedule::class, 'brunch_id');
    }
    public function contact_info(){

        return $this->hasMany(contact_info::class, 'brunch_id');
    }
    public function serving_ways(){

        return $this->hasMany(serving_ways::class, 'brunch_id');
    }
    public function payment_services(){

        return $this->hasMany(payment_services::class, 'brunch_id');
    }

    public function revenue(){

        return $this->hasMany(Revenue::class , 'client_id' , 'client_id');
    }

    public function cat_meal(){

        return $this->hasMany(meals_categories::class , 'brunch_id' , 'id');
    }

}
