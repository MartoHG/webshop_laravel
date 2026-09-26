<?php

namespace App\Models;

use App\Notifications\RestablecerClave;
use App\Notifications\VerificarEmail;
use Database\Factories\UsuarioFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    protected $table = 'usuarios';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'email',
        'telefono',
        'password',
        'rol',
        'activo',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function setDniAttribute(string $dni): void
    {
        $this->attributes['dni'] = preg_replace('/\D+/', '', $dni) ?? '';
    }

    public function setEmailAttribute(string $email): void
    {
        $this->attributes['email'] = mb_strtolower(trim($email));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerificarEmail);
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerClave($token));
    }
}
