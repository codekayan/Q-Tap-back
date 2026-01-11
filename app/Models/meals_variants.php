<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class meals_variants extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'price', 'meals_id' ,'brunch_id' ];
}
