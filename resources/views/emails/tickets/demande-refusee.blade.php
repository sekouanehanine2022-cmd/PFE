<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Demande refusée</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Bonjour {{ $ticket->demandeur->name }},</p>
    <p>
        Votre demande d'{{ $typeDemande }} <strong>« {{ $ticket->titre }} »</strong>
        ne peut malheureusement pas être acceptée.
    </p>
    <div style="background:#fff1f2; border:1px solid #fecdd3; padding:12px;">
        <strong>Motif du refus :</strong><br>
        {{ $ticket->demande->motif_refus }}
    </div>
    <p>Cordialement,<br>Support IT</p>
</body>
</html>
