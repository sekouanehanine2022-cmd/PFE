<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affectation extends Model
{
    protected $table = 'affectations';

    protected $fillable = [
        'personnel_id',
        'ticket_id',
        'materiel_id',
        'date_debut',
        'date_fin',
        'date_retour',
        'statut',
        'notes',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'date_retour' => 'date',
    ];

    // Une affectation appartient à un personnel
    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

    // Une affectation appartient à un ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Le matériel affecté (PC, Écran, Imprimante...)
    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
