<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class affiliate_payment_info extends Model
{
    use HasFactory;


    protected $fillable = [
        'affiliate_id',
        'payment_way',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'wallet_provider',
        'wallet_number',



        'name_on_credit_card',
        'credit_card_number',
        'address',
    ];

}
