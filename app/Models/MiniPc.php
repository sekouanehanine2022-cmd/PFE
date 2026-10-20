<?php

namespace App\Models;

class MiniPc extends Materiel
{
    protected $table = 'mini_pcs';

    // materiel_id identifie cette fiche et la relie a la ligne correspondante dans materiels.
    protected $primaryKey = 'materiel_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'numero_serie',
        'materiel_id',
        'cpu',
        'ram',
        'stockage',
        'os',
        'adresse_mac',
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

    // Definit le lien pour retrouver les affectations associes a cet element.
    public function affectations()
    {
        return $this->hasMany(Affectation::class, 'materiel_id', 'materiel_id');
    }

    // Definit le lien pour retrouver les tickets associes a cet element.
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'materiel_id', 'materiel_id');
    }
}
