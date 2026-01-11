<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class affiliate_clicks extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_code',
        'clicks',
    ];
}
