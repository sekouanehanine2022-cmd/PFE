<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Recupere la liste et les compteurs des notifications pour afficher la page.
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->with([
                'ticket',
                'affectation.personnel.user',
                'affectation.materiel',
                'emprunt.etudiant.user',
                'emprunt.pcPortable.materiel',
                'cable',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        $nombreNonLues = Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return view('notifications', compact('notifications', 'nombreNonLues'));
    }

    // Verifie le destinataire puis marque uniquement sa notification comme lue.
    public function lire(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        if ($notification->ticket_id) {
            return redirect()->route('tickets.index', ['ticket' => $notification->ticket_id]);
        }

        if ($notification->affectation_id) {
            if ($request->user()->personnel?->role !== 'admin') {
                return redirect()->route('mon-materiel.index');
            }
            return redirect()->route('affectations.index', ['affectation' => $notification->affectation_id]);
        }

        if ($notification->emprunt_id) {
            if ($request->user()->personnel?->role !== 'admin') {
                return redirect()->route('mon-materiel.index');
            }
            return redirect()->route('emprunts.index', ['emprunt' => $notification->emprunt_id]);
        }

        if ($notification->cable_id) {
            return redirect()->route('cables.index', ['cable' => $notification->cable_id]);
        }

        return redirect()->route('notifications.index');
    }

    // Marque comme lues uniquement les notifications du compte connecte.
    public function toutLire(Request $request)
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()
            ->route('notifications.index')
            ->with('success', __('messages.notifications_toutes_lues'));
    }
}
