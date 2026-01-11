<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class affiliate_log extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'status',
    ];
}
