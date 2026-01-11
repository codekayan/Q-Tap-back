<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'menu',
        'users',
        'orders',
        'wallet',
        'setting',
        'support',
        'dashboard',
        'customers_log',
        'brunch_id',
    ];
}
