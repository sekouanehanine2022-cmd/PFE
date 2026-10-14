<?php

namespace App\Models;

class Etudiant extends User
{
    protected $table = 'etudiants';

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

    public function utilisateur()
    {
        return $this->user();
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

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
