<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageChatSupport extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_support_id',
        'message',
        'type',
        'status',
    ];




    public function ChatSupport(){

        return $this->belongsTo(ChatSupport::class , 'chat_support_id' , 'id');
    }


}
