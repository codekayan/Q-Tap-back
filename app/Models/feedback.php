<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class feedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['client_id' , 'star', 'emoji', 'your_goals', 'missing_Q-tap_Menus', 'comment' , 'publish' , 'brunch_id'] ;


        public function client()
    {
        return $this->belongsTo(qtap_clients::class , 'client_id' , 'id');
    }

}
