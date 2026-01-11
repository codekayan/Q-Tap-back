<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSupport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'status',
    ];




    public function user(){

        return $this->belongsTo(User::class , 'user_id' , 'id');
    }

    public function MessageChatSupport(){

        return $this->hasMany(MessageChatSupport::class);
    }

}
