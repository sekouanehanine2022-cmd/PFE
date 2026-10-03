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
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function affectation()
    {
        return $this->belongsTo(Affectation::class);
    }

    public function emprunt()
    {
        return $this->belongsTo(Emprunt::class);
    }

    public function cable()
    {
        return $this->belongsTo(Cable::class);
    }
}
