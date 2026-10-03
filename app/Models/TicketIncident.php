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

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
