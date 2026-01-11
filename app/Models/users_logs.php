<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class users_logs extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'brunch_id',
    ];

    public function user()
    {
        return $this->belongsTo(restaurant_user_staff::class , 'user_id' , 'id');
    }
}
