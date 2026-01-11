<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'governorate_id'];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }





}
