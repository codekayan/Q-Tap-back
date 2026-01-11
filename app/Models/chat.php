<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class chat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'sender_type',
        'message'
];

}
