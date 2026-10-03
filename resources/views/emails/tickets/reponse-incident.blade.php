<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Réponse du support IT</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Bonjour {{ $ticket->demandeur->name }},</p>
    <p>
        @if ($estModification)
            La réponse du support IT concernant votre incident
            <strong>« {{ $ticket->titre }} »</strong> a été mise à jour.
        @else
            Le support IT a répondu à votre incident
            <strong>« {{ $ticket->titre }} »</strong>.
        @endif
    </p>
    <div style="background:#eff6ff; border:1px solid #bfdbfe; padding:12px;">
        <strong>Réponse du support IT :</strong><br>
        {{ $ticket->incident->reponse_admin }}
    </div>
    <p>Vous pouvez également consulter cette réponse depuis la page <strong>Mes tickets</strong>.</p>
    <p>Cordialement,<br>Support IT</p>
</body>
</html>
