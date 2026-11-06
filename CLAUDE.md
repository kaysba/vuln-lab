# CLAUDE.md

Instructions pour Claude Code sur ce dépôt.

## But du projet

vuln-lab est une application web volontairement vulnérable, pour m'entraîner
au pentest. Elle contient plusieurs modules de failles, chacun avec 3 niveaux
de difficulté (easy / medium / hard). Usage strictement local, jamais exposée
sur Internet.

## Stack

- PHP 8 + Apache (image officielle `php:8.x-apache`)
- MySQL 8
- Docker Compose : un service `web`, un service `db`
- Pas de framework PHP : du PHP simple, pour que les failles restent lisibles

## État actuel

Stack choisie, pas encore de code. L'architecture et les commandes
(build, lancement, reset de la base) seront ajoutées ici après la planification.

## Règles Docker

- Les ports sont publiés uniquement sur la boucle locale :
  `127.0.0.1:8080:80`, jamais `8080:80`.
- La base MySQL n'est pas publiée sur l'hôte (accessible seulement depuis `web`).
- Identifiants de la base dans `.env` (ignoré), avec un `.env.example` commité.
- La base doit pouvoir être remise à zéro par un script d'initialisation.

## Environnement

- Windows, avec PowerShell et Git Bash.
- La branche par défaut est `main`, protégée : on y entre uniquement par PR.

## Règles de code

- Tout code volontairement vulnérable est marqué :
  `// VULN: <type> - level <easy|medium|hard>`
- Ne jamais corriger ni "sécuriser" un code marqué `VULN`, sauf demande explicite.
- Un module de faille = un dossier, avec sa page, sa logique et sa correction documentée.
- Uniquement des données factices : jamais de vrais secrets ni de vraies données.
- L'application n'écoute que sur 127.0.0.1.
- Aucune dépendance ajoutée sans me le dire d'abord.

## Workflow Git

- Ne jamais commiter ni pousser sur `main`.
- Une branche par tâche : `feature/`, `fix/`, `docs/`, `chore/`, `refactor/`.
- Commits au format Conventional Commits : `type(zone): description`.
- Petits commits, un changement logique chacun.
- Ne jamais utiliser `git push --force` ni `git reset --hard` sans me demander.


## Façon de travailler

- Propose un plan avant d'écrire du code.
- Une tâche à la fois, en expliquant ce que tu changes.
- Ne touche pas aux fichiers hors de la tâche demandée.
- Avant de conclure : l'application démarre et je peux tester à la main.
- Quand je ne comprends pas, réexplique plus simplement, avec un exemple concret.

## Mise à jour de ce fichier

Une fois la stack choisie, remplacer "État actuel" par l'architecture et les
vraies commandes (build, lancement, tests, test unique).