<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcPortable extends Model
{
    // Nom de la table dans la BDD
    protected $table = 'pc_portables';

    // Colonnes qu'on peut remplir
    protected $fillable = [
        'reference',
        'nom',
        'marque',
        'numero_serie',
        'cpu',
        'ram',
        'stockage',
        'os',
        'ecran',
        'etat',
        'emplacement',
        'date_achat',
    ];

    // Colonnes de type date
    protected $casts = [
        'date_achat' => 'date',
    ];

    // Un PC portable peut avoir plusieurs affectations
    public function affectations()
    {
        return $this->morphMany(Affectation::class, 'materiel');
    }

    // Un PC portable peut avoir plusieurs emprunts
    public function emprunts()
    {
        return $this->morphMany(Emprunt::class, 'materiel');
    }

    // Un PC portable peut avoir plusieurs tickets
    public function tickets()
    {
        return $this->morphMany(Ticket::class, 'materiel');
    }
}