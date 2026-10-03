<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
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
            return redirect()->route('affectations.index', ['affectation' => $notification->affectation_id]);
        }

        if ($notification->emprunt_id) {
            return redirect()->route('emprunts.index', ['emprunt' => $notification->emprunt_id]);
        }

        if ($notification->cable_id) {
            return redirect()->route('cables.index', ['cable' => $notification->cable_id]);
        }

        return redirect()->route('notifications.index');
    }

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
