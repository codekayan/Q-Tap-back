<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customers_Visits_restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'visits',
        'brunch_id',
    ];
}
