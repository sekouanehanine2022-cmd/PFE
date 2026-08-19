<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Imprimante extends Model
{
    protected $table = 'imprimantes';

    protected $fillable = [
        'reference',
        'nom',
        'marque',
        'numero_serie',
        'type_impression',
        'couleur',
        'connexion',
        'vitesse',
        'etat',
        'emplacement',
        'date_achat',
    ];

    protected $casts = [
        'date_achat' => 'date',
        'couleur'    => 'boolean',
    ];

    public function affectations()
    {
        return $this->morphMany(Affectation::class, 'materiel');
    }

    public function emprunts()
    {
        return $this->morphMany(Emprunt::class, 'materiel');
    }

    public function tickets()
    {
        return $this->morphMany(Ticket::class, 'materiel');
    }
}