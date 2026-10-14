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
        return $this->belongsTo(Etudiant::class, 'etudiant_id', 'user_id');
    }

    // Un emprunt appartient à un ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Un emprunt concerne uniquement un PC portable.
    public function pcPortable()
    {
        return $this->belongsTo(PcPortable::class, 'materiel_id', 'materiel_id');
    }

    public function materiel()
    {
        return $this->belongsTo(Materiel::class, 'materiel_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
