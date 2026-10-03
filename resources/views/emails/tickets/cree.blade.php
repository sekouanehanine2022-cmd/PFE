<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Ticket enregistré</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Bonjour {{ $ticket->demandeur->name }},</p>
    <p>
        Votre ticket <strong>« {{ $ticket->titre }} »</strong> a bien été enregistré.
        Votre demande sera traitée dans les plus brefs délais.
    </p>
    <p>Vous pouvez suivre son évolution depuis la page <strong>Mes tickets</strong>.</p>
    <p>Cordialement,<br>Support IT</p>
</body>
</html>
