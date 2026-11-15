# CLAUDE.md

Instructions for Claude Code on this repository.

## Project goal

vuln-lab is an intentionally vulnerable web application (a small forum) for me
to practice pentesting. Each vulnerability lives in a real forum feature, with
3 difficulty levels (easy / medium / hard) chosen through a global selector.
Strictly local use, never exposed to the Internet.

The detailed plan and the roadmap are in `docs/PLAN.md`. It is the reference.

## Stack

- PHP 8 + Apache (official `php:8.x-apache` image), mysqli extension
- MySQL 8
- Docker Compose: `web` and `db` services
- No PHP framework: plain PHP, so the vulnerabilities stay readable
- mysqli for vulnerable code. PDO only in the writeup fixes.

## Target tree

```
vuln-lab/
├── CLAUDE.md
├── README.md
├── .env.example            # dummy values, the real .env is never committed
├── docker-compose.yml
├── docker/web/Dockerfile
├── db/                     # schema and fake data, run on first start
├── docs/
│   ├── PLAN.md
│   └── writeups/           # written by me, see TEMPLATE.md
├── .claude/agents/         # advisor, coder, tester, mechanic
└── src/                    # web root served by Apache
    ├── config/             # db.php, level.php, bootstrap.php
    ├── includes/           # header.php (nav + level selector), footer.php
    ├── assets/style.css
    ├── index.php           # news feed
    ├── login.php           # module 1: SQL injection + weak authentication
    ├── profile.php         # module 2: IDOR
    ├── messages.php        # module 2: IDOR
    ├── search.php          # module 3: reflected XSS
    ├── post.php            # module 4: stored XSS
    ├── admin/index.php     # module 5: broken access control
    ├── flags.php           # flag submission + progress
    └── reset.php           # database reset
```

## Code rules

- All the app is in English: page text, code comments, seed data, docs.
- All intentionally vulnerable code is marked:
  `// VULN: <type> - level <easy|medium|hard>`
- Never fix or "secure" code marked `VULN`, unless explicitly asked.
- The difficulty level is stored only in the session (`$_SESSION['level']`,
  `easy` by default). A single function `get_level()` reads it. No `config` table.
- Each module picks its behavior with `switch (get_level())`.
- Unsalted MD5 passwords for all accounts (intentional, to be documented).
- XSS: the flag is in a non-HttpOnly cookie, so JavaScript must really run
  to get it.
- Fake data only: never real secrets or real data.
- Flags must never be readable by the vulnerable app's MySQL user, and never
  written in plain text in `db/` or in the code. They derive from
  `FLAG_SECRET` (in `.env`).
- No dependency added without telling me first.

## Docker rules

- Port published on loopback only: `127.0.0.1:8080:80`, never `8080:80`.
- The MySQL database is not published on the host (reachable only from `web`).
- Credentials and `FLAG_SECRET` in `.env` (ignored). `.env.example` is committed
  with dummy values.
- The database must be resettable: `reset.php` (POST only, with
  confirmation) or `docker compose down -v`.

## Git workflow

- `main` = working releases only. `dev` = integration branch.
- Never commit or push directly to `main` or `dev` (protected: everything goes through a PR).
- One branch per task in `docs/PLAN.md`, created from `dev`: `feature/`, `fix/`, `docs/`, `chore/`.
- Task PR into `dev` with Squash and merge.
- `dev` -> `main` only at a release, when everything works, as a merge commit (no squash).
- Commits in Conventional Commits format: `type(scope): description`.
- Small commits, one logical change each.
- Never use `git push --force` or `git reset --hard` without asking me.

## How to work

- Propose a plan before writing code.
- One task at a time, explaining what you change.
- Do not touch files outside the requested task.
- Before wrapping up: the app starts and I can test it by hand.
- I want to find the vulnerabilities myself: do not give me the exploits, unless I ask.
- Writeups are written by me. You may prepare the skeleton, never the content.
- When I don't understand, explain again more simply, with a concrete example.

## Agents (called from the main session, they don't call each other)

- Module design, plan -> plan mode in the main session
- Implementing a task -> `coder`
- Repetitive task with no decision -> `mechanic`
- Checking the levels after each module -> `tester`
- Critical review -> `advisor`

Mandatory moments for the `advisor`:
1. Before coding the first module (design of the 3 levels).
2. After the first module is done.
3. Before the v0.1.0 release.

Escalation:
- 2 failures with `mechanic` -> `coder`.
- `tester` answers RESISTS on easy or EXPLOITABLE on hard -> the task is not done.

## Commands

- Config: `cp .env.example .env` then change the values.
- Build + start: `docker compose up --build -d`, then http://127.0.0.1:8080
- Logs: `docker compose logs -f web` (or `db`)
- Stop: `docker compose down`
- Full database reset: `docker compose down -v`

## Updating this file

At the end of the roadmap, check that the commands and the tree match
reality.
