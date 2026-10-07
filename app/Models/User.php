<?php

namespace App\Models;

use App\Notifications\VerifierAdresseEmail;
use App\Notifications\ReinitialiserMotDePasse;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'mot_de_passe_change' => 'boolean',
            'acces_bloque'      => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function doubleAuthentificationActive(): bool
    {
        return filled($this->two_factor_secret)
            && $this->two_factor_confirmed_at !== null;
    }

    // Un user peut être un personnel
    public function personnel()
    {
        return $this->hasOne(Personnel::class);
    }

    // Un user peut être un étudiant
    public function etudiant()
    {
        return $this->hasOne(Etudiant::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifierAdresseEmail());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ReinitialiserMotDePasse($token));
    }
}
