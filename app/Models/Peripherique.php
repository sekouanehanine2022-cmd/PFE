<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peripherique extends Model
{
    protected $table = 'peripheriques';

    protected $fillable = [
        'reference',
        'nom',
        'marque',
        'numero_serie',
        'sous_type',
        'connexion',
        'disposition',
        'retro_eclairage',
        'etat',
        'emplacement',
        'date_achat',
    ];

    protected $casts = [
        'date_achat'      => 'date',
        'retro_eclairage' => 'boolean',
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