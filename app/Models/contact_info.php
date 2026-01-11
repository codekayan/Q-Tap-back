<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class contact_info extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['brunch_id' , 'business_email' , 'business_phone' , 'website' , 'facebook' , 'twitter' , 'instagram' , 'address'];
}
