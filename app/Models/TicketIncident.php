<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketIncident extends Model
{
    protected $table = 'ticket_incidents';

    protected $primaryKey = 'ticket_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'reponse_admin',
        'date_reponse',
    ];

    protected $casts = [
        'date_reponse' => 'datetime',
    ];

    // Definit le lien vers tickets pour retrouver l'element associe.
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
