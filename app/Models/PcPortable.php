<?php

namespace App\Models;

class PcPortable extends Materiel
{
    // Nom de la table dans la BDD
    protected $table = 'pc_portables';

    // materiel_id identifie cette fiche et la relie a la ligne correspondante dans materiels.
    protected $primaryKey = 'materiel_id';

    public $incrementing = false;

    protected $keyType = 'int';

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

    // Definit le lien vers materiels pour retrouver l'element associe.
    public function getRouteKeyName(): string
    {
        return 'numero_serie';
    }

    // Definit le lien vers materiels pour retrouver l'element associe.
    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }

    // Recupere le nom dans la ligne liee de la table materiels.
    public function getNomAttribute()
    {
        return $this->materiel?->nom;
    }

    // Recupere la marque dans la ligne liee de la table materiels.
    public function getMarqueAttribute()
    {
        return $this->materiel?->marque;
    }

    // Recupere l'etat dans la ligne liee de la table materiels.
    public function getEtatAttribute()
    {
        return $this->materiel?->etat;
    }

    // Recupere l'emplacement dans la ligne liee de la table materiels.
    public function getEmplacementAttribute()
    {
        return $this->materiel?->emplacement;
    }

    // Recupere la date d'achat dans la ligne liee de la table materiels.
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
        return $this->hasMany(Emprunt::class, 'materiel_id', 'materiel_id');
    }

    // Un PC portable peut avoir plusieurs tickets
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'materiel_id', 'materiel_id');
    }
}
