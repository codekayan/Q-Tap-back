<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ticketSupport extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'Customer_Name',
        'client_id',
        'brunch_id',
        'Customer_Email',
        'Customer_Phone',
        'status',
        'content'
    ];
}
