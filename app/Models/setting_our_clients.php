<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class setting_our_clients extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['img' , 'title'];
}
