# FKCCI Election Voting Slip System

CodeIgniter 4 + MySQL application for the Federation of Karnataka Chambers of
Commerce & Industry (FKCCI): RFID-based voting slip issuance at 10 simultaneous
election-day counters, with one-vote-per-company enforcement, QR slip
verification, and admin tools for master data and the voter log.

An HTML prototype of the full flow lives in [`prototype/`](prototype/) — see
its README for the confirmed scope and UX decisions this build implements.

## Requirements

- PHP 8.2+ (`intl`, `mbstring`, `mysqlnd` extensions)
- MySQL 5.7+/8.x or MariaDB 10.4+
- Composer

## Setup

```bash
composer install
cp env .env
```

Edit `.env`:

```ini
CI_ENVIRONMENT = development   # or production

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = fkcci_election
database.default.username = <db user>
database.default.password = <db password>
database.default.DBDriver = MySQLi
```

Generate a real encryption key (don't skip this — session/cookie security
depends on it):

```bash
php spark key:generate
```

Create the database, then run migrations and seed an initial admin + one
operator account per counter:

```bash
mysql -u root -e "CREATE DATABASE fkcci_election CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
php spark migrate --all
php spark db:seed InitialUsersSeeder
```

Run it:

```bash
php spark serve
```

## Architecture

See [`prototype/README.md`](prototype/README.md#suggested-mysql-schema-starting-point-for-the-ci4-build)
for the original schema sketch and the design decisions behind it (why
one-vote-per-company is enforced with a DB-level generated-column unique
index rather than only an app-level check, why photos are a separate optional
upload rather than an Excel column, etc). The sections below describe what
this build actually implements — read the prototype README first for *why*.

### Roles & auth

Two roles, one `users` table, session-based auth (no third-party auth
package — the surface is deliberately small):

- **admin** — full access: master data import, voter log, dashboard.
- **operator** — assigned to exactly one counter (`users.assigned_counter`),
  can only use the counter scan/issue screen and reprint slips.

### Core flow (`/counter`)

1. Operator scans an RFID tag (Zebra reader in keyboard-wedge mode — it just
   types into a focused input, no SDK integration).
2. `POST /counter/lookup` resolves the tag against `members`, and reports one
   of: not found / already voted (with who/when/where) / eligible.
3. `POST /counter/issue` re-validates inside a DB transaction and inserts the
   vote. The **one vote per company** rule is enforced by MySQL itself via a
   generated column + unique index on `votes` (see migration
   `CreateVotesTable`) — not just an application check — so it holds even
   under a genuine race between two of the 10 counters.
4. The slip (Member ID, Name, Company, QR code, serial, counter, timestamp)
   renders for the browser print dialog. It never includes a photo — thermal
   slip printers render photos as unusable grayscale blobs.
5. Every issued slip stays reachable afterwards at `/slip/{serial}` to view
   on screen or reprint to a different printer — a member's tag can only be
   scanned once (the vote is recorded), so this is the only way to recover
   from a failed print.

### QR verification (`/verify/{serial}`, public, no auth)

Scanning the slip's QR code opens this page showing the member's photo (if
on file), name, company, and whether the slip is valid/void. No auth — this
is meant to be scanned by hall-entry staff on their own phones.

### Admin (`/admin/...`, admin role only)

- **Master data** — Excel import (`phpoffice/phpspreadsheet`) for
  RFID/Member ID/Name/Company/Designation/Mobile/Email, and a *separate*,
  optional bulk photo upload (`.zip`, matched to members by filename ==
  Member ID). Neither Excel import nor voting requires a photo to exist.
- **Voter log** — search/filter by counter/status, void a wrongly-issued
  slip with a reason, CSV export.
- **Dashboard** — turnout stats, votes per counter, companies pending.

## Directory notes

- `prototype/` — the static HTML/JS prototype (kept for reference and
  side-by-side UX comparison; not served by the app).
- `app/Controllers/Counter`, `app/Controllers/Admin`, `app/Controllers/Auth` —
  route handlers by area.
- `app/Database/Migrations` — schema. `app/Database/Seeds` — initial users.
- `writable/uploads/photos/` — uploaded member photos (gitignored).
