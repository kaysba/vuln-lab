# vuln-lab plan

One branch = one PR into `dev` = Squash and merge. `dev` -> `main` at release time
(merge commit). Never commit directly to `main` or `dev`.
Writeups are written by me (see `docs/writeups/TEMPLATE.md`).

## Approved decisions

- Stack: PHP 8 + Apache, MySQL 8, Docker Compose, no framework.
- Port `127.0.0.1:8080:80` only. MySQL not published.
- Difficulty level stored only in the session (`easy` by default). No `config` table.
- Flags: computed on the fly in PHP with HMAC-SHA256(`FLAG_SECRET`, module + level),
  never stored (not in the database, not in `db/`, not in the code). The database only
  holds progress. `web` does not receive the MySQL root password. The app's MySQL user
  only has rights on its own database (no FILE privilege).
- Unsalted MD5 passwords for all accounts (intentional).
- `reset.php`: POST only, with confirmation.
- mysqli for vulnerable code, PDO in the writeup fixes.
- XSS: the flag is in a non-HttpOnly cookie, to be retrieved by running JavaScript.
- 5 modules x 3 levels = 15 flags.
- The whole app is in English (pages, comments, seed data, docs).

## Modules and levels

| Module | easy | medium | hard |
|---|---|---|---|
| 1. Login (SQLi + weak auth) | concatenated query, plain-text password in the comparison | naive filtering that can be bypassed | almost parameterized, one field stays vulnerable |
| 2. Profiles / private messages (IDOR) | no owner check | bypassable check (editable parameter or identifier) | check present but incomplete on one endpoint |
| 3. Search (reflected XSS) | input echoed as is | bypassable filter | correct escaping except one forgotten context |
| 4. Posts / comments (stored XSS) | content stored and displayed raw | bypassable sanitizing | allowlist with one dangerous attribute allowed |
| 5. Admin (access control) | reachable by direct URL | client-side or parameter check | incomplete check on a sub-action |

## Roadmap

- [x] `docs/project-plan`: this kit (CLAUDE.md, agents, PLAN.md, README, .gitignore)
- [x] `chore/scaffold-docker`: docker-compose, Dockerfile, minimal `src/index.php`.
      Validation: `docker compose up --build` shows the page on 127.0.0.1:8080,
      and the port cannot be reached from another machine.
- [x] `chore/db-schema-seed`: schema (users, posts, comments, messages, progress),
      fake data, `config/db.php`, decision on flags.
      Validation: tables populated, the app user reads no flag.
- [x] `feature/reset-db`: `reset.php` (POST + confirmation).
      Validation: after changing data, the reset restores the initial state.
- [ ] `feature/layout-and-level`: header, footer, sober dark CSS, `config/level.php`,
      global selector. Validation: the chosen level persists during the session.
- [ ] `feature/flags-progress`: `flags.php` + progress page.
      Validation: a valid flag is marked solved, a wrong one is rejected.
- [ ] `feature/auth-login-sqli`: module 1, 3 levels. Then `docs/writeup-login-sqli`.
- [ ] `feature/idor-profile-messages`: module 2. Then `docs/writeup-idor`.
- [ ] `feature/xss-reflected-search`: module 3. Then `docs/writeup-xss-reflected`.
- [ ] `feature/xss-stored-posts`: module 4. Then `docs/writeup-xss-stored`.
- [ ] `feature/admin-access-control`: module 5. Then `docs/writeup-admin-access`.
- [ ] `docs/update-claude-md`: real commands in CLAUDE.md and README, screenshots.
- [ ] Release `v0.1.0`: clean clone, `docker compose up`, checklist, PR `dev` -> `main`,
      tag and GitHub notes.

After each module: `tester` (levels), then commit. The `advisor` reviews before
the first module, after the first module, and before the release.

## Risks

- Network exposure: `127.0.0.1` is the only guard. Repeat it in the README.
- A vulnerability in one module must not allow reading the flags of the others.
- Windows: line endings and Docker volume permissions. Also test on the IUT's Linux machine.
