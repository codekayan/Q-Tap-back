<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class revenue_restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'brunch_id',
        'order_id',
        'ref_number',
        'revenue',
    ];
}
