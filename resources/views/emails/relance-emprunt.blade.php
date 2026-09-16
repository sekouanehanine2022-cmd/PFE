<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rappel de retour de materiel</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <p>Bonjour {{ $nomEtudiant }},</p>

    @if ($joursRestants > 1)
        <p>
            Nous vous rappelons que vous devez rendre le materiel
            <strong>{{ $nomMateriel }}</strong> dans {{ $joursRestants }} jours.
        </p>
    @elseif ($joursRestants === 1)
        <p>
            Nous vous rappelons que vous devez rendre le materiel
            <strong>{{ $nomMateriel }}</strong> demain.
        </p>
    @elseif ($joursRestants === 0)
        <p>
            Nous vous rappelons que vous devez rendre le materiel
            <strong>{{ $nomMateriel }}</strong> aujourd'hui.
        </p>
    @else
        <p>
            Nous vous rappelons que la date de retour du materiel
            <strong>{{ $nomMateriel }}</strong> est depassee.
        </p>
    @endif

    <p>Date de retour prevue : <strong>{{ $dateFinPrevue }}</strong>.</p>

    <p>Merci de vous rapprocher du support IT si vous avez besoin d'une prolongation.</p>

    <p>
        Cordialement,<br>
        Support IT
    </p>
</body>
</html>
