<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emprunt extends Model
{
    protected $table = 'emprunts';

    protected $fillable = [
        'etudiant_id',
        'ticket_id',
        'pc_numero_serie',
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

    // Un emprunt concerne uniquement un PC portable.
    public function pcPortable()
    {
        return $this->belongsTo(PcPortable::class, 'pc_numero_serie', 'numero_serie');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
