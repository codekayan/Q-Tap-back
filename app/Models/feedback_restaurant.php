<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class feedback_restaurant extends Model
{
    use HasFactory , SoftDeletes;

    protected $fillable = ['client_id' , 'phone' ,'star', 'emoji', 'your_goals', 'missing_Q-tap_Menus', 'comment' , 'publish' , 'brunch_id'] ;

    public function brunch()
    {
        return $this->belongsTo(qtap_clients_brunchs::class , 'brunch_id' , 'id');
    }
}
