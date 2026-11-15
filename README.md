# vuln-lab

Application web **volontairement vulnérable** (un petit forum) pour s'entraîner au pentest.
Cinq modules de failles, chacun avec 3 niveaux : easy / medium / hard.

> ⚠️ **Avertissement** : ce code contient des failles réelles et exploitables.
> À lancer uniquement en local, sur `127.0.0.1`. Ne jamais déployer sur Internet
> ni sur un réseau partagé.

## Stack

PHP 8 + Apache, MySQL 8, lancés avec Docker Compose.

## Modules

| # | Module | Faille |
|---|---|---|
| 1 | Connexion | Injection SQL, authentification faible |
| 2 | Profils et messages privés | IDOR |
| 3 | Recherche | XSS reflétée |
| 4 | Posts et commentaires | XSS stockée |
| 5 | Panneau d'administration | Contrôle d'accès cassé |

## Lancement

(à compléter après la tâche `chore/scaffold-docker`)

## Writeups

Un writeup par module dans `docs/writeups/` : la faille, l'exploitation, la correction.
