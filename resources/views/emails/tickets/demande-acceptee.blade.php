<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Demande acceptée</title></head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <p>Bonjour {{ $ticket->demandeur->name }},</p>
    <p>
        Votre demande d'{{ $typeDemande }} <strong>« {{ $ticket->titre }} »</strong> a été acceptée.
    </p>
    <p>
        Vous pouvez passer à l'administration pour récupérer
        {{ $ticket->materiel?->nom ? 'le matériel '.$ticket->materiel->nom : 'votre matériel' }}.
    </p>
    <p>Cordialement,<br>Support IT</p>
</body>
</html>
