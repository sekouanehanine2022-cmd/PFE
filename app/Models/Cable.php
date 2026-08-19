<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cable extends Model
{
    protected $table = 'cables';

    protected $fillable = [
        'reference',
        'type_cable',
        'longueur',
        'quantite',
        'quantite_disponible',
        'seuil_alerte',
        'couleur',
        'etat',
        'emplacement',
    ];

    protected $casts = [
        'quantite'            => 'integer',
        'quantite_disponible' => 'integer',
        'seuil_alerte'        => 'integer',
    ];
}