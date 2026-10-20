<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDemande extends Model
{
    protected $table = 'ticket_demandes';

    protected $primaryKey = 'ticket_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'type_demande',
        'motif_refus',
        'date_refus',
    ];

    protected $casts = [
        'date_refus' => 'datetime',
    ];

    // Definit le lien vers tickets pour retrouver l'element associe.
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
