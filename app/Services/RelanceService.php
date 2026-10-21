<?php

namespace App\Services;

use App\Mail\RelanceAffectationMail;
use App\Mail\RelanceEmpruntMail;
use App\Models\Affectation;
use App\Models\Emprunt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RelanceService
{
    // Recoit le service qui enregistre les notifications dans l'application.
    public function __construct(private readonly NotificationService $notifications) {}

    // Cherche les retours dans 14, 7 ou 1 jour, cree les notifications et envoie les emails non encore envoyes.
    public function envoyer(): array
    {

        $resultat = ['notifications' => 0, 'emails' => 0, 'erreurs' => 0];
        $aujourdhui = now('Europe/Paris')->startOfDay();


        foreach ([Affectation::class, Emprunt::class] as $classe) {
            $affectation = $classe === Affectation::class;

            $champDate = $affectation ? 'date_fin' : 'date_fin_prevue';

            $query = $classe::query()->whereNull('date_retour')->whereDate('date_debut', '<=', $aujourdhui->toDateString());

            $query->where(function ($query) use ($champDate, $aujourdhui) {
                foreach ([14, 7, 1] as $palier) {
                    $query->orWhereDate($champDate, $aujourdhui->copy()->addDays($palier)->toDateString());
                }
            });
            $affectation ? $query->where('statut', 'active') : $query->where('statut', '!=', 'rendu')->whereHas('pcPortable');

            // Recupere les resultats progressivement et traite chaque operation.
            foreach ($query->lazyById() as $operation) {
                try {
                    DB::transaction(function () use ($classe, $operation, $affectation, $champDate, $aujourdhui, &$resultat) {
                        // Verrouille cette ligne pendant la transaction : un autre traitement doit attendre pour la modifier.
                        $courante = $classe::query()->lockForUpdate()->find($operation->id);
                        // Ignore l'operation si elle a disparu, a ete rendue ou n'est plus autorisee.
                        if (! $courante || $courante->date_retour || ! $courante->$champDate
                            || ($affectation ? $courante->statut !== 'active' : $courante->statut === 'rendu' || ! $courante->pcPortable)) {
                            return;
                        }
                        $date = \Carbon\Carbon::parse($courante->$champDate->toDateString(), 'Europe/Paris');

                        $palier = (int) $aujourdhui->diffInDays($date, false);
                        if (! in_array($palier, [14, 7, 1], true)) {
                            return;
                        }


                        $notification = $this->notifications->notifierRelance($courante, $palier);
                        $resultat['notifications'] += (int) $notification->wasRecentlyCreated;
                        $mailer = config('mail.default');


                        if ($notification->email_envoye_at || ! in_array(config('mail.mailers.'.$mailer.'.transport'), ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true)) {
                            return;
                        }
                        try {

                            $email = $affectation ? $courante->personnel?->user?->email : $courante->etudiant?->user?->email;
                            if (! $email) {
                                throw new \RuntimeException('Adresse email introuvable.');
                            }
                            Mail::to($email)->send($affectation ? new RelanceAffectationMail($courante) : new RelanceEmpruntMail($courante));

                            $notification->update(['email_envoye_at' => now()]);
                            $resultat['emails']++;
                        } catch (Throwable $exception) {

                            $resultat['erreurs']++;
                            Log::error('Echec email de relance.', ['notification_id' => $notification->id, 'exception' => $exception]);
                        }
                    });
                } catch (Throwable $exception) {
                    $resultat['erreurs']++;
                    Log::error('Echec de relance automatique.', ['operation' => $classe, 'id' => $operation->id, 'exception' => $exception]);
                }
            }
        }

        // Renvoie le nombre de notifications, d'emails et d'erreurs a la commande Artisan.
        return $resultat;
    }
}
