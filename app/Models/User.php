<?php

namespace App\Models;

use App\Notifications\VerifierAdresseEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
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
}
