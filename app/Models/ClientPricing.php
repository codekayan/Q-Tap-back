<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'pricing_id',
        'status',
        'image',
        'ramin_order',
        'expired_at',
        'payment_methodes',
        'pricing_way',
        'original_price',
        'original_total_price',
        'discount_percentage',
        'discounted_price',
        'final_price',
        'coupon_code',
        'number_of_branches',
        'discount_details'
    ];

    protected $casts = [
        'original_price' => 'float',
        'original_total_price' => 'float',
        'discounted_price' => 'float',
        'final_price' => 'float',
        'discount_details' => 'array'
    ];

    // app/Models/Campaign.php
    public function pricing()
    {
            return $this->belongsTo(pricing::class , 'pricing_id' , 'id');
    }

    public function client(){

        return $this->belongsTo(qtap_clients::class , 'client_id' , 'id');
    }


    public function SubscriptionChangeRequest()
    {
            return $this->hasMany(SubscriptionChangeRequest::class);
    }



    public function getPriceDetailsAttribute()
    {
        return [
            'base_price' => $this->original_price,
            'total_before_discount' => $this->original_total_price,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->original_total_price - $this->discounted_price,
            'price_after_discount' => $this->discounted_price,
            'final_price' => $this->final_price,
            'coupon_code' => $this->coupon_code,
            'branches_count' => $this->number_of_branches,
            'pricing_plan' => $this->pricing->name ?? null,
            'pricing_way' => $this->pricing_way,
            'payment_method' => $this->payment_methodes,
            'discount_details' => $this->discount_details
        ];
    }
}

