<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tables extends Model
{
    use HasFactory;
    protected $fillable = ['brunch_id', 'area_id', 'name' , 'size' , 'link'];


    public function area_info()
    {
        return $this->belongsTo(area::class ,'area_id' , 'id');
    }
}
