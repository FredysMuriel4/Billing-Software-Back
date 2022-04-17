<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nit',
        'email',
        'password',
        'role_id'
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
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getTransmitterInvoice()
    {
        return $this->hasMany(InvoiceHeader::class, 'invoice_transmitter_id');
    }

    public function getReceiverInvoice()
    {
        return $this->hasMany(InvoiceHeader::class, 'invoice_receiver_id');
    }

    public function respond($status, $data, $error, $message)
    {
        return json_encode([
            'status' => $status, //Código HTTP de la respuesta
            'data' => $data, //Datos solicitados
            'error' => $error, //Error ocurrido
            'message' => $message //Mensaje para el usuario
        ]);
    }
}
