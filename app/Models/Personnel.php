<?php

namespace App\Models;

class Personnel extends User
{
    protected $table = 'personnels';

    // user_id identifie ce profil et le relie au compte dans users.
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'service',
        'poste',
        'telephone',
        'type_contrat',
        'role',
    ];

    // Un personnel appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Donne acces au compte utilisateur lie a ce profil.
    public function utilisateur()
    {
        return $this->user();
    }

    // Definit le lien pour retrouver les affectations associes a cet element.
    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    // Definit le lien pour retrouver les affectations associes a cet element.
    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    // Un personnel peut avoir plusieurs affectations
    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'personnel_id', 'user_id');
    }
}
