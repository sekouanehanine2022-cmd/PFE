<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Incident resolu</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Bonjour {{ $ticket->demandeur->name }},</p>
    <p>
        Votre incident <strong>« {{ $ticket->titre }} »</strong> a ete traite et marque comme resolu par le support IT.
    </p>
    <p>Vous pouvez consulter la reponse du support depuis la page <strong>Mes tickets</strong>.</p>
    <p>Cordialement,<br>Support IT</p>
</body>
</html>
