<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class clients_transactions extends Model
{
    use HasFactory , SoftDeletes;

    protected $fillable = [
        'client_id' , 'amount','Reverence_no' , 'status'
    ];
}
