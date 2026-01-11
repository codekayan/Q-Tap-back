<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Campaigns extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name' , 'commission' , 'limit'];

    // app/Models/Campaign.php
public function affiliateRevenues()
{
    return $this->hasMany(affiliate_Revenues::class , 'campaign_id');
}

}
