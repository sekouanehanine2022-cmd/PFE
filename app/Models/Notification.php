<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'ticket_id',
        'affectation_id',
        'emprunt_id',
        'cable_id',
        'type',
        'message',
        'read_at',
        'date_echeance',
        'email_envoye_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'date_echeance' => 'date:Y-m-d',
        'email_envoye_at' => 'datetime',
    ];

    // Definit le lien vers comptes utilisateurs pour retrouver l'element associe.
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Definit le lien vers tickets pour retrouver l'element associe.
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Definit le lien vers affectations pour retrouver l'element associe.
    public function affectation()
    {
        return $this->belongsTo(Affectation::class);
    }

    // Definit le lien vers emprunts pour retrouver l'element associe.
    public function emprunt()
    {
        return $this->belongsTo(Emprunt::class);
    }

    // Definit le lien vers cables pour retrouver l'element associe.
    public function cable()
    {
        return $this->belongsTo(Cable::class);
    }
}
