<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Renvoie les donnees Notification autorisees au compte connecte au format JSON.
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'nombre_non_lues' => $notifications->whereNull('read_at')->count(),
            'notifications' => $notifications
                ->map(fn (Notification $notification) => $this->formater($notification))
                ->values(),
        ]);
    }

    // Verifie le destinataire puis marque uniquement sa notification comme lue.
    public function lire(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Notification marquee comme lue.',
            'notification' => $this->formater($notification->fresh()),
        ]);
    }

    // Marque comme lues uniquement les notifications du compte connecte.
    public function toutLire(Request $request): JsonResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => __('messages.notifications_toutes_lues'),
            'nombre_non_lues' => 0,
        ]);
    }

    // Prepare le message, l etat de lecture et la destination de la notification mobile.
    private function formater(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'message' => $notification->message,
            'est_lue' => $notification->read_at !== null,
            'date_creation' => $notification->created_at?->toIso8601String(),
            'destination' => $notification->ticket_id ? 'tickets' : (($notification->affectation_id || $notification->emprunt_id) ? 'materiel' : null),
        ];
    }
}
