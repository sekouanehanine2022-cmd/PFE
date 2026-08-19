<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personnel extends Model
{
    protected $table = 'personnels';

    protected $fillable = [
        'user_id',
        'service',
        'poste',
        'telephone',
        'role',
    ];

    // Un personnel appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Un personnel peut avoir plusieurs affectations
    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }
}