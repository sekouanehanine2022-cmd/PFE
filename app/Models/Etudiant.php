<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etudiant extends Model
{
    protected $table = 'etudiants';

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
        return $this->belongsTo(User::class);
    }

    // Un étudiant peut avoir plusieurs emprunts
    public function emprunts()
    {
        return $this->hasMany(Emprunt::class);
    }
}