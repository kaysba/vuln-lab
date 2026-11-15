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

1. Copier `.env.example` en `.env` et changer les valeurs (mots de passe, `FLAG_SECRET`).
2. Lancer : `docker compose up --build -d`
3. Ouvrir http://127.0.0.1:8080
4. Logs : `docker compose logs -f web` (ou `db`)
5. Arrêter : `docker compose down`
6. Tout remettre à zéro (base comprise) : `docker compose down -v`

L'application n'écoute que sur 127.0.0.1 (boucle locale) : elle n'est pas accessible depuis le réseau, et ne doit jamais être exposée sur Internet.

## Writeups

Un writeup par module dans `docs/writeups/` : la faille, l'exploitation, la correction.
