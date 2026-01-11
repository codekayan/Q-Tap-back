<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'value',
        'order_id',
    ];



    public function brunchs(){

        return $this->belongsTo(qtap_clients_brunchs::class , 'client_id', 'client_id');
    }

    public function client(){

        return $this->belongsTo(qtap_clients::class , 'client_id', 'id');
    }



}
