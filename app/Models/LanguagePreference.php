<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LanguagePreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'language',
        'languageable_id',
        'languageable_type'
    ];

    public function languageable()
    {
        return $this->morphTo();
    }
}
