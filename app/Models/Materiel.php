<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materiel extends Model
{
    protected $table = 'materiels';

    protected $fillable = [
        'type_materiel',
        'nom',
        'marque',
        'etat',
        'emplacement',
        'date_achat',
    ];

    protected $casts = [
        'date_achat' => 'date',
    ];

    public function pcPortable()
    {
        return $this->hasOne(PcPortable::class);
    }

    public function miniPc()
    {
        return $this->hasOne(MiniPc::class);
    }

    public function ecran()
    {
        return $this->hasOne(Ecran::class);
    }

    public function imprimante()
    {
        return $this->hasOne(Imprimante::class);
    }

    public function clavier()
    {
        return $this->hasOne(Clavier::class);
    }

    public function souris()
    {
        return $this->hasOne(Souris::class);
    }

    public function casque()
    {
        return $this->hasOne(Casque::class);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
