<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'titre',
        'description',
        'type',
        'priorite',
        'statut',
        'demandeur_id',
        'technicien_id',
        'materiel_type',
        'materiel_id',
    ];

    // Le ticket appartient à celui qui l'a créé
    public function demandeur()
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    // Le ticket appartient au technicien qui le traite
    public function technicien()
    {
        return $this->belongsTo(User::class, 'technicien_id');
    }

    // Le matériel concerné (PC, Écran, Imprimante...)
    public function materiel()
    {
        return $this->morphTo();
    }

    // Un ticket peut générer une affectation
    public function affectation()
    {
        return $this->hasOne(Affectation::class);
    }

    // Un ticket peut générer un emprunt
    public function emprunt()
    {
        return $this->hasOne(Emprunt::class);
    }
}