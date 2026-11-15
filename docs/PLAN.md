# Plan vuln-lab

Une branche = une PR vers `main` = Squash and merge. Jamais de commit direct sur `main`.
Les writeups sont écrits par moi (voir `docs/writeups/TEMPLATE.md`).

## Décisions validées

- Stack : PHP 8 + Apache, MySQL 8, Docker Compose, pas de framework.
- Port `127.0.0.1:8080:80` uniquement. MySQL non publié.
- Niveau de difficulté stocké uniquement en session (`easy` par défaut). Pas de table `config`.
- Flags : jamais lisibles par l'utilisateur MySQL de l'appli vulnérable, dérivés de
  `FLAG_SECRET` (`.env`), jamais en clair dans `db/`. Mécanisme exact (base séparée
  avec utilisateur dédié, ou calcul à partir du secret) à confirmer dans la tâche 2.
- Mots de passe en MD5 sans sel pour tous les comptes (volontaire).
- `reset.php` : POST uniquement, avec confirmation.
- mysqli pour le code vulnérable, PDO dans les correctifs des writeups.
- XSS : le flag est dans un cookie non HttpOnly, à récupérer en exécutant du JavaScript.
- 5 modules x 3 niveaux = 15 flags.

## Modules et niveaux

| Module | easy | medium | hard |
|---|---|---|---|
| 1. Login (SQLi + auth faible) | requête concaténée, mot de passe en clair dans la comparaison | filtrage naïf contournable | presque paramétré, un champ reste vulnérable |
| 2. Profils / MP (IDOR) | aucun contrôle du propriétaire | contrôle contournable (paramètre ou identifiant modifiable) | contrôle présent mais incomplet sur un endpoint |
| 3. Recherche (XSS reflétée) | saisie réaffichée telle quelle | filtre contournable | échappement correct sauf un contexte oublié |
| 4. Posts / commentaires (XSS stockée) | contenu stocké et affiché brut | nettoyage contournable | liste blanche avec un attribut dangereux autorisé |
| 5. Admin (contrôle d'accès) | accessible par URL directe | contrôle côté client ou paramètre | vérification incomplète sur une sous-action |

## Roadmap

- [ ] `docs/project-plan` : ce kit (CLAUDE.md, agents, PLAN.md, README, .gitignore)
- [ ] `chore/scaffold-docker` : docker-compose, Dockerfile, `src/index.php` minimal.
      Validation : `docker compose up --build` affiche la page sur 127.0.0.1:8080,
      et le port n'est pas joignable depuis une autre machine.
- [ ] `chore/db-schema-seed` : schéma (users, posts, comments, messages, progress),
      données factices, `config/db.php`, décision sur les flags.
      Validation : tables peuplées, l'utilisateur de l'appli ne lit aucun flag.
- [ ] `feature/reset-db` : `reset.php` (POST + confirmation).
      Validation : après modification des données, le reset restaure l'état initial.
- [ ] `feature/layout-and-level` : header, footer, CSS sombre sobre, `config/level.php`,
      sélecteur global. Validation : le niveau choisi persiste pendant la session.
- [ ] `feature/flags-progress` : `flags.php` + page de progression.
      Validation : un flag valide est marqué résolu, un faux est refusé.
- [ ] `feature/auth-login-sqli` : module 1, 3 niveaux. Puis `docs/writeup-login-sqli`.
- [ ] `feature/idor-profile-messages` : module 2. Puis `docs/writeup-idor`.
- [ ] `feature/xss-reflected-search` : module 3. Puis `docs/writeup-xss-reflected`.
- [ ] `feature/xss-stored-posts` : module 4. Puis `docs/writeup-xss-stored`.
- [ ] `feature/admin-access-control` : module 5. Puis `docs/writeup-admin-access`.
- [ ] `docs/update-claude-md` : commandes réelles dans CLAUDE.md et README, captures d'écran.
- [ ] Release `v0.1.0` : clone propre, `docker compose up`, checklist, tag et notes GitHub.

Après chaque module : `testeur` (niveaux), puis commit. Le `conseiller` relit avant
le premier module, après le premier module, et avant la release.

## Risques

- Exposition réseau : `127.0.0.1` est la seule garde. À répéter dans le README.
- Une faille d'un module ne doit pas permettre de lire les flags des autres.
- Windows : fins de ligne et permissions des volumes Docker. Tester aussi sur la machine Linux de l'IUT.
