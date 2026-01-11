<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class meals_extra extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'price',
        'variants_id',
        'brunch_id',
        'meals_id',
    ];
}
