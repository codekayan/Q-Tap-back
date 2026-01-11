<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class meals_size extends Model
{
    use HasFactory;
    protected $fillable = [
        'size',
        'price',

        // Foreign key for meals
        'meals_id',

        // Foreign key for branches
        'brunch_id',
    ];
}
