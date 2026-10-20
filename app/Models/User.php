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

    // Liste les champs que create() et update() peuvent remplir a partir d'un tableau.
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // Cache ces informations lorsque le compte est renvoye au format JSON.
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    // Definit les conversions Eloquent, le hachage du mot de passe et le chiffrement des secrets 2FA.
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Le modele hache les nouveaux mots de passe avant leur enregistrement.
            'password'          => 'hashed',
            'mot_de_passe_change' => 'boolean',
            'acces_bloque'      => 'boolean',
            // Le secret TOTP doit pouvoir etre relu : il est chiffre avec la cle de l application.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // Definit le lien vers la fiche Personnel associee.
    public function doubleAuthentificationActive(): bool
    {
        return filled($this->two_factor_secret)
            && $this->two_factor_confirmed_at !== null;
    }

    // Un user peut être un personnel
    public function personnel()
    {
        return $this->hasOne(Personnel::class, 'user_id', 'id');
    }

    // Un user peut être un étudiant
    public function etudiant()
    {
        return $this->hasOne(Etudiant::class, 'user_id', 'id');
    }

    // Definit le lien pour retrouver les notifications associes a cet element.
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Envoie la notification personnalisee de verification de l adresse e-mail.
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifierAdresseEmail());
    }

    // Envoie le lien personnalise de reinitialisation du mot de passe.
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ReinitialiserMotDePasse($token));
    }
}
