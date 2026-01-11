<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class orders_processing extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'brunch_id',
        'status',
        'time',
        'stage',
        'delivery_rider_id',
        'note'
    ];



    public function user()
    {
        return $this->belongsTo(restaurant_user_staff::class, 'user_id', 'id');
    }



    public function order()
    {
        return $this->belongsTo(orders::class, 'order_id', 'id');
    }


}
