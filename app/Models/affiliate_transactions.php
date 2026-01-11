<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class affiliate_transactions extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'affiliate_id',
        'brunch_id',
        'amount',
        'Reverence_no',
        'status',
        'created_at',
        'updated_at',
    ];

    public function affiliate()
    {
        return $this->belongsTo(qtap_affiliate::class);
    }
}
