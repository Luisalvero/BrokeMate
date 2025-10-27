# BrokeMate

Lightweight expense splitting app for groups of friends. PHP 8.2, SQLite (PDO), and vanilla HTML/CSS/JS. No frameworks.

## Quick start

Prereqs: Docker and Docker Compose.

1) Build and start the app

```
docker compose up --build
```

2) Initialize the database and seed demo data (in another terminal)

```
docker compose exec brokemate php database/seed.php
```

3) Open http://localhost:8080

### Demo logins

- owner@example.com / Owner123!
- alice@example.com / Alice123!
- bob@example.com / Bob123!

Or use the Home page to create a temporary guest account.

## Running locally without Docker

Ensure PHP 8.2+ with pdo_sqlite enabled.

```
php -v     # confirm PHP version
php -m | grep sqlite

php -S 0.0.0.0:8080 -t public
```

Then in another terminal initialize the DB:

```
php database/seed.php
```

## Project structure

```
public/index.php           # front controller + router
public/assets/{css,js}
app/Controllers/*.php
app/Views/*.php            # small PHP templates
app/Lib/{Auth.php,DB.php,CSRF.php,Validator.php,Util.php}
config/config.php
database/{migrations.sql, seed.php}
scripts/smoke.php
Dockerfile
docker-compose.yml
README.md
```

DB file: `database/app.db` (auto-created on first run)

## Features

- Auth (register/login/logout), guest quick mode, profile settings
- Groups: create, join via invite code, dashboard with recent activity
- Expenses: add with split methods (even, shares, exact, percent), participants and allocations
- Settlements: record payments, validate against current debt
- Ledger: per-member net balances and a simplified transfers proposal (greedy)
- In-app notifications: group joined, expense includes you, settlement received
- Search + pagination on expenses/settlements
- Public read-only group view (optional)
- CSRF protection, prepared statements, output escaping, simple rate limiting
- Mobile-first UI, light/dark mode toggle, tiny canvas charts (no external libs)

## Security notes

- CSRF tokens added to all POST forms and validated server-side.
- All DB calls use prepared statements (PDO) and `PRAGMA foreign_keys=ON`.
- `htmlspecialchars` wrappers used for output escaping.
- Simple per-IP rate limiting for auth and group join using a file-based token bucket in `tmp/`.
- No file uploads; avatar is URL-only and validated for scheme.

## Simplifying debts algorithm

We compute each member’s net balance (paid − owed − settlements). Balances sum to ~0. The “Simplify Debts” action uses a greedy matcher:

1. Split members into creditors (positive) and debtors (negative).
2. Repeatedly match the largest debtor with the largest creditor.
3. Transfer `min(abs(debt), abs(credit))` and update both.
4. Stop when all small residuals are below a cent.

This gives a small set of transfers that clear most or all debts.

## Migrations and seed

Initial schema is in `database/migrations.sql`. Seed script will migrate and insert demo data:

```
php database/seed.php
```

## Smoke test

Run a tiny smoke test to validate DB connectivity and ledger sanity:

```
php scripts/smoke.php
```

## Notes

- Serve via PHP’s built-in server: `php -S 0.0.0.0:8080 -t public` (Dockerfile does this).
- SQLite DB is stored in `database/app.db`. It’s .gitignored by default.
- If you change schema, re-run seed or write a migration step.
