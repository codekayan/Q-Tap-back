<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class qtap_clients extends Authenticatable implements JWTSubject
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
        'otp',
        'user_type',
        'order_id',
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


    public function logs()
    {
        return $this->hasMany(clients_logs::class, 'client_id');
    }

    public function brunchs(){

        return $this->hasMany(qtap_clients_brunchs::class , 'client_id');
    }


    public function ClientPricing(){

        return $this->hasMany(ClientPricing::class );
    }


    public function languagePreference()
    {
        return $this->morphOne(LanguagePreference::class, 'languageable');
    }


}
