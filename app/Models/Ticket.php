<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'titre',
        'description',
        'date_resolution',
        'priorite',
        'statut',
        'demandeur_id',
        'technicien_id',
        'materiel_id',
    ];

    protected $casts = [
        'date_resolution' => 'datetime',
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
        return $this->belongsTo(Materiel::class);
    }

    public function incident()
    {
        return $this->hasOne(TicketIncident::class);
    }

    public function demande()
    {
        return $this->hasOne(TicketDemande::class);
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

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
