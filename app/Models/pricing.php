<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class pricing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name' , 'description', 'orders_limit', 'feature', 'monthly_price', 'yearly_price', 'is_active'] ;




   /* public function qtap_clients_brunchs(){

        return $this->hasMany(qtap_clients_brunchs::class ,  'pricing_id');
    }

*/
    public function ClientPricing(){

        return $this->hasMany(ClientPricing::class );
    }


    public function SubscriptionChangeRequest()
    {
            return $this->hasMany(SubscriptionChangeRequest::class);
    }

}

