<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class workschedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable =['day' ,'brunch_id' , 'opening_time' , 'closing_time'];
}
