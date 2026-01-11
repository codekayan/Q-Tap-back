<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;


class qtap_affiliate extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'mobile',
        'birth_date',
        'country',
        'status',
        'email',
        'password',
        'img',
        'code',
        'user_type',
        'otp',
        'Campaigns',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function payment_info()
    {
        return $this->hasOne(affiliate_payment_info::class, 'affiliate_id');
    }

    public function clicks_count()
    {
        return $this->hasMany(affiliate_clicks::class, 'affiliate_code' , 'code');
    }


    public function languagePreference()
    {
        return $this->morphOne(LanguagePreference::class, 'languageable');
    }
}
