<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class affiliate_Revenues extends Model
{
    use HasFactory;
    protected $fillable = [
        'affiliate_id',
        'brunch_id',
        'amount',
        'value_order',
        'commission',
        'campaign_id',
        'order_id',
        'affiliate_code'
    ];


    public function brunch_user(){
        return $this->belongsTo(qtap_clients_brunchs::class , 'brunch_id' , 'id');
    }
}
