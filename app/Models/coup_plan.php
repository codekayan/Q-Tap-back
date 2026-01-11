<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class coup_plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'discount',
        'status',
    ];
}
