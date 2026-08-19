<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MiniPc extends Model
{
    protected $table = 'mini_pcs';

    protected $fillable = [
        'reference',
        'nom',
        'marque',
        'numero_serie',
        'cpu',
        'ram',
        'stockage',
        'os',
        'adresse_mac',
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