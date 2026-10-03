<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcPortable extends Model
{
    // Nom de la table dans la BDD
    protected $table = 'pc_portables';

    protected $primaryKey = 'numero_serie';

    public $incrementing = false;

    protected $keyType = 'string';

    // Colonnes qu'on peut remplir
    protected $fillable = [
        'numero_serie',
        'materiel_id',
        'adresse_mac',
        'cpu',
        'ram',
        'stockage',
        'os',
        'ecran',
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

    // Un PC portable peut avoir plusieurs affectations
    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'materiel_id', 'materiel_id');
    }

    // Un PC portable peut avoir plusieurs emprunts
    public function emprunts()
    {
        return $this->hasMany(Emprunt::class, 'pc_numero_serie', 'numero_serie');
    }

    // Un PC portable peut avoir plusieurs tickets
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'materiel_id', 'materiel_id');
    }
}
