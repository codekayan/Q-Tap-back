<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class meals_special_offers extends Model
{
    use HasFactory;
    protected $fillable =[
        'name',
        'description',
        'discount',
        'before_discount',
        'after_discount',
        'img',
        'meals_id',
        'brunch_id',
    ];
}
