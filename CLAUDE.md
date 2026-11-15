# CLAUDE.md

Instructions pour Claude Code sur ce dépôt.

## But du projet

vuln-lab est une application web volontairement vulnérable (un petit forum) pour
m'entraîner au pentest. Chaque faille vit dans une vraie fonctionnalité du forum,
avec 3 niveaux de difficulté (easy / medium / hard) choisis par un sélecteur global.
Usage strictement local, jamais exposée sur Internet.

Le plan détaillé et la roadmap sont dans `docs/PLAN.md`. C'est la référence.

## Stack

- PHP 8 + Apache (image officielle `php:8.x-apache`), extension mysqli
- MySQL 8
- Docker Compose : services `web` et `db`
- Pas de framework PHP : du PHP simple, pour que les failles restent lisibles
- mysqli pour le code vulnérable. PDO uniquement dans les correctifs des writeups.

## Arborescence cible

```
vuln-lab/
├── CLAUDE.md
├── README.md
├── .env.example            # valeurs factices, le vrai .env n'est jamais commité
├── docker-compose.yml
├── docker/web/Dockerfile
├── db/                     # schéma et données factices, joués au 1er démarrage
├── docs/
│   ├── PLAN.md
│   └── writeups/           # écrits par moi, voir TEMPLATE.md
├── .claude/agents/         # conseiller, codeur, testeur, mecanique
└── src/                    # racine web servie par Apache
    ├── config/             # db.php, level.php, bootstrap.php
    ├── includes/           # header.php (nav + sélecteur de niveau), footer.php
    ├── assets/style.css
    ├── index.php           # fil d'actualité
    ├── login.php           # module 1 : injection SQL + authentification faible
    ├── profile.php         # module 2 : IDOR
    ├── messages.php        # module 2 : IDOR
    ├── search.php          # module 3 : XSS reflétée
    ├── post.php            # module 4 : XSS stockée
    ├── admin/index.php     # module 5 : contrôle d'accès cassé
    ├── flags.php           # soumission des flags + progression
    └── reset.php           # remise à zéro de la base
```

## Règles de code

- Tout code volontairement vulnérable est marqué :
  `// VULN: <type> - level <easy|medium|hard>`
- Ne jamais corriger ni "sécuriser" un code marqué `VULN`, sauf demande explicite.
- Le niveau de difficulté est stocké uniquement en session (`$_SESSION['level']`,
  `easy` par défaut). Une seule fonction `get_level()` le lit. Pas de table `config`.
- Chaque module choisit son comportement avec `switch (get_level())`.
- Mots de passe en MD5 sans sel pour tous les comptes (volontaire, à documenter).
- XSS : le flag est dans un cookie non HttpOnly, pour qu'il faille réellement
  exécuter du JavaScript pour le récupérer.
- Uniquement des données factices : jamais de vrais secrets ni de vraies données.
- Les flags ne doivent jamais être lisibles par l'utilisateur MySQL de l'application
  vulnérable, et jamais écrits en clair dans `db/` ni dans le code. Ils dérivent de
  `FLAG_SECRET` (dans `.env`).
- Aucune dépendance ajoutée sans me le dire d'abord.

## Règles Docker

- Port publié uniquement sur la boucle locale : `127.0.0.1:8080:80`, jamais `8080:80`.
- La base MySQL n'est pas publiée sur l'hôte (accessible seulement depuis `web`).
- Identifiants et `FLAG_SECRET` dans `.env` (ignoré). `.env.example` est commité
  avec des valeurs bidon.
- La base doit pouvoir être remise à zéro : `reset.php` (POST uniquement, avec
  confirmation) ou `docker compose down -v`.

## Workflow Git

- Ne jamais commiter ni pousser sur `main` (protégée : tout passe par une PR).
- Une branche par tâche de `docs/PLAN.md` : `feature/`, `fix/`, `docs/`, `chore/`.
- Commits au format Conventional Commits : `type(zone): description`.
- Petits commits, un changement logique chacun.
- Ne jamais utiliser `git push --force` ni `git reset --hard` sans me demander.

## Façon de travailler

- Propose un plan avant d'écrire du code.
- Une tâche à la fois, en expliquant ce que tu changes.
- Ne touche pas aux fichiers hors de la tâche demandée.
- Avant de conclure : l'application démarre et je peux tester à la main.
- Je veux trouver les failles moi-même : ne me donne pas les exploits, sauf si je le demande.
- Les writeups sont écrits par moi. Tu peux préparer le squelette, jamais le contenu.
- Quand je ne comprends pas, réexplique plus simplement, avec un exemple concret.

## Agents (appelés depuis la session principale, ils ne s'appellent pas entre eux)

- Design d'un module, plan -> mode plan de la session principale
- Implémentation d'une tâche -> `codeur`
- Tâche répétitive sans décision -> `mecanique`
- Contrôle des niveaux après chaque module -> `testeur`
- Relecture critique -> `conseiller`

Moments obligatoires pour le `conseiller` :
1. Avant de coder le premier module (design des 3 niveaux).
2. Après le premier module terminé.
3. Avant la release v0.1.0.

Escalade :
- 2 échecs avec `mecanique` -> `codeur`.
- `testeur` répond RÉSISTE sur easy ou EXPLOITABLE sur hard -> la tâche n'est pas terminée.

## Commandes

À compléter après la tâche `chore/scaffold-docker` (build, lancement, reset, logs).

## Mise à jour de ce fichier

À la fin de la roadmap, vérifier que les commandes et l'arborescence reflètent
la réalité.
