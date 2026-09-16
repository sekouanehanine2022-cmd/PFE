<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Souris extends Model
{
    protected $table = 'souris';

    protected $primaryKey = 'numero_serie';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'numero_serie',
        'materiel_id',
        'connexion',
        'dpi',
        'nombre_boutons',
    ];

    protected $casts = [
        'dpi' => 'integer',
        'nombre_boutons' => 'integer',
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

    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'materiel_id', 'materiel_id');
    }

    public function emprunts()
    {
        return $this->hasMany(Emprunt::class, 'materiel_id', 'materiel_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'materiel_id', 'materiel_id');
    }
}
