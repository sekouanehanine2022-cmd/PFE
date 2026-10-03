<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Nouveau ticket</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    @php
        $types = [
            'incident' => 'Incident',
            'affectation' => "Demande d'affectation",
            'emprunt' => "Demande d'emprunt",
        ];
    @endphp

    <p>Bonjour,</p>
    <p>Un nouveau ticket vient d'être créé.</p>

    <div style="background:#f8fafc; border:1px solid #dbe3ec; padding:14px;">
        <p style="margin:0 0 8px;"><strong>Demandeur :</strong> {{ $ticket->demandeur->name }}</p>
        <p style="margin:0 0 8px;"><strong>Type :</strong> {{ $types[$typeTicket] ?? ucfirst($typeTicket) }}</p>
        <p style="margin:0 0 8px;"><strong>Titre :</strong> {{ $ticket->titre }}</p>
        <p style="margin:0 0 8px;"><strong>Priorité :</strong> {{ ucfirst($ticket->priorite) }}</p>
        <p style="margin:0;"><strong>Créé le :</strong> {{ $ticket->created_at->format('d/m/Y à H:i') }}</p>
    </div>

    <p>
        <a href="{{ route('tickets.index') }}">Ouvrir la page des tickets</a>
    </p>
    <p>Cordialement,<br>Application de gestion du parc informatique</p>
</body>
</html>
