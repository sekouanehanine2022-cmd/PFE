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

    // Definit le lien vers la fiche PcPortable associee.
    public function pcPortable()
    {
        return $this->hasOne(PcPortable::class);
    }

    // Definit le lien vers la fiche MiniPc associee.
    public function miniPc()
    {
        return $this->hasOne(MiniPc::class);
    }

    // Definit le lien vers la fiche Ecran associee.
    public function ecran()
    {
        return $this->hasOne(Ecran::class);
    }

    // Definit le lien vers la fiche Imprimante associee.
    public function imprimante()
    {
        return $this->hasOne(Imprimante::class);
    }

    // Definit le lien vers la fiche Clavier associee.
    public function clavier()
    {
        return $this->hasOne(Clavier::class);
    }

    // Definit le lien vers la fiche Souris associee.
    public function souris()
    {
        return $this->hasOne(Souris::class);
    }

    // Definit le lien vers la fiche Casque associee.
    public function casque()
    {
        return $this->hasOne(Casque::class);
    }

    // Definit le lien pour retrouver les affectations associes a cet element.
    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }

    // Definit le lien pour retrouver les tickets associes a cet element.
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
