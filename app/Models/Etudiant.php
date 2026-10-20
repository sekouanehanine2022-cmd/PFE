<?php

namespace App\Models;

class Etudiant extends User
{
    protected $table = 'etudiants';

    // user_id identifie ce profil et le relie au compte dans users.
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'type',
        'promotion',
        'date_fin_formation',
        'etablissement',
    ];

    protected $casts = [
        'date_fin_formation' => 'date',
    ];

    // Un étudiant appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Donne acces au compte utilisateur lie a ce profil.
    public function utilisateur()
    {
        return $this->user();
    }

    // Definit le lien pour retrouver les emprunts associes a cet element.
    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    // Definit le lien pour retrouver les emprunts associes a cet element.
    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    // Un étudiant peut avoir plusieurs emprunts
    public function emprunts()
    {
        return $this->hasMany(Emprunt::class, 'etudiant_id', 'user_id');
    }
}
