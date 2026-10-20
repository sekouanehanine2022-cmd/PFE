# Tests automatiques et deploiement Alwaysdata

## Fonctionnement

Le workflow `.github/workflows/ci-cd.yml` lance les tests Laravel sur les pushs
et les pull requests vers `main`. Il utilise SQLite en memoire et aucun vrai
envoi de mail. Il compile aussi les ressources avec Vite.

Le job de deploiement depend de la reussite du job de tests. Il transfere les
fichiers testes, sauvegarde le code distant, active temporairement la maintenance,
installe les dependances, execute les migrations puis optimise Laravel.
Il ne lance ni `migrate:fresh`, ni les seeders et ne remplace pas le `.env` distant.
Le dossier transfere ne contient pas de secrets ni de dependances locales.

## Activation sur GitHub

1. Relire puis committer et pousser les fichiers necessaires du projet, dont les
   nouveaux services et tests actuellement non suivis. Ne pas pousser le `.env`.
2. Verifier une premiere execution verte dans l'onglet Actions de GitHub.
3. Creer un environnement GitHub nomme `production` et limiter son deploiement
   a `main`. Ajouter une approbation manuelle si l'offre GitHub le permet.
4. Configurer les variables Actions du depot :

| Variable | Valeur |
| --- | --- |
| DEPLOY_HOST | ssh-iegparcinfo.alwaysdata.net |
| DEPLOY_USER | iegparcinfo |
| DEPLOY_PATH | /home/iegparcinfo/PFE |
| DEPLOY_URL | https://iegparcinfo.alwaysdata.net |
| DEPLOY_ENABLED | false pendant la preparation, puis true pour activer |

5. Creer une cle SSH dediee au deploiement. Installer sa partie publique dans
   le compte SSH Alwaysdata et enregistrer la partie privee dans le secret Actions
   `ALWAYSDATA_SSH_PRIVATE_KEY`. Cela donne au workflow acces au compte distant :
   ne jamais mettre la cle privee dans le depot ou dans une conversation.
6. Enregistrer la ligne de cle publique du serveur dans le secret
   `ALWAYSDATA_SSH_KNOWN_HOSTS`, apres comparaison de son empreinte avec celle
   affichee par Alwaysdata. Ne pas desactiver la verification des cles SSH.
7. Verifier qu'une sauvegarde de la base de production est disponible avant
   d'activer le premier deploiement. Les archives du script sauvegardent le code,
   pas MySQL. Les migrations doivent etre relues : une migration destructive
   peut supprimer des donnees meme sans `migrate:fresh`.
8. Activer DEPLOY_ENABLED et lancer le workflow manuellement depuis `main`.
   Verifier les journaux et le site avant de presenter la chaine comme operationnelle.

## Limites

Le transfert est prepare avant la maintenance. La mise a jour du code n'est pas
atomique : elle utilise rsync sans suppression des fichiers distants. Une suppression
de fichier voulue doit donc etre geree explicitement. En cas d'echec, l'etape est rouge
et le script tente de retirer la maintenance ; le retour a l'ancienne version reste
manuel depuis la sauvegarde du code, avec traitement separe des migrations.

Conserver les sauvegardes hors du dossier public et surveiller leur espace disque.
Ne pas annoncer une CI/CD active tant que le workflow n'est pas pousse et qu'un
premier passage GitHub (puis un deploiement) n'a pas ete verifie.
