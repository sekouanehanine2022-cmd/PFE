<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
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

    private function formater(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'message' => $notification->message,
            'est_lue' => $notification->read_at !== null,
            'date_creation' => $notification->created_at?->toIso8601String(),
            'destination' => $notification->ticket_id ? 'tickets' : null,
        ];
    }
}
