<?php

namespace App\Models;

class Imprimante extends Materiel
{
    protected $table = 'imprimantes';

    protected $primaryKey = 'materiel_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'numero_serie',
        'materiel_id',
        'type_impression',
        'couleur',
        'connexion',
        'vitesse',
    ];

    protected $casts = [
        'couleur'    => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'numero_serie';
    }

    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }

    public function getNomAttribute()
    {
        return $this->materiel?->nom;
    }

    public function getMarqueAttribute()
    {
        return $this->materiel?->marque;
    }

    public function getEtatAttribute()
    {
        return $this->materiel?->etat;
    }

    public function getEmplacementAttribute()
    {
        return $this->materiel?->emplacement;
    }

    public function getDateAchatAttribute()
    {
        return $this->materiel?->date_achat;
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'materiel_id', 'materiel_id');
    }
}
