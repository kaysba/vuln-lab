# vuln-lab

An **intentionally vulnerable** web application (a small forum) to practice pentesting.
Five vulnerability modules, each with 3 levels: easy / medium / hard.

> ⚠️ **Warning**: this code contains real, exploitable vulnerabilities.
> Run it locally only, on `127.0.0.1`. Never deploy it on the Internet
> or on a shared network.

## Stack

PHP 8 + Apache, MySQL 8, run with Docker Compose.

## Modules

| # | Module | Vulnerability |
|---|---|---|
| 1 | Login | SQL injection, weak authentication |
| 2 | Profiles and private messages | IDOR |
| 3 | Search | Reflected XSS |
| 4 | Posts and comments | Stored XSS |
| 5 | Admin panel | Broken access control |

## Getting started

1. Copy `.env.example` to `.env` and change the values (passwords, `FLAG_SECRET`).
2. Start: `docker compose up --build -d`
3. Open http://127.0.0.1:8080
4. Logs: `docker compose logs -f web` (or `db`)
5. Stop: `docker compose down`
6. Reset everything (database included): `docker compose down -v`

The application only listens on 127.0.0.1 (loopback): it is not reachable from the network, and must never be exposed to the Internet.

## Writeups

One writeup per module in `docs/writeups/`: the vulnerability, the exploitation, the fix.
