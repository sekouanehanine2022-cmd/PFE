<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ecran extends Model
{
    protected $table = 'ecrans';

    protected $fillable = [
        'reference',
        'nom',
        'marque',
        'numero_serie',
        'taille',
        'resolution',
        'dalle',
        'taux_rafraichissement',
        'etat',
        'emplacement',
        'date_achat',
    ];

    protected $casts = [
        'date_achat' => 'date',
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