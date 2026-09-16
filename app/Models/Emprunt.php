<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emprunt extends Model
{
    protected $table = 'emprunts';

    protected $fillable = [
        'etudiant_id',
        'ticket_id',
        'materiel_id',
        'date_debut',
        'date_fin_prevue',
        'date_retour',
        'statut',
        'notes',
    ];

    protected $casts = [
        'date_debut'      => 'date',
        'date_fin_prevue' => 'date',
        'date_retour'     => 'date',
    ];

    // Un emprunt appartient à un étudiant
    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class);
    }

    // Un emprunt appartient à un ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Le matériel emprunté (PC, Écran, Imprimante...)
    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }
}
