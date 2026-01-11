<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class meals extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'img',
        'Brief',
        'Description',
        'Ingredients',
        'Calories',
        'Time',
        'Tax',
        'price',
        'discount_precentage',
        'categories_id',
        'brunch_id',
        'price_small',
        'price_medium',
        'price_large',
        'limit_variants',
    ];



    public function variants()
    {
        return $this->hasMany(meals_variants::class, 'meals_id');
    }

    public function extras()
    {
        return $this->hasMany(meals_extra::class, 'meals_id');
    }


    public function meals_special_offer(){

        return $this->hasMany(meals_special_offers::class , 'meals_id' , 'id');
    }
}
