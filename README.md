# FKCCI Membership & Election Voting System

CodeIgniter 4 + MySQL application for the Federation of Karnataka Chambers of
Commerce & Industry (FKCCI). Two connected halves:

- **Membership management** — online self-registration against the paper
  "Application for Membership" form, online payment, a two-stage approval
  workflow (Membership Committee → Managing Committee), RFID tag issuance,
  and PVC ID card printing.
- **Election voting** — RFID-based voting slip issuance at 10 simultaneous
  election-day counters, one-vote-per-company enforcement, QR slip
  verification, and exit-desk EVM vote confirmation (camera QR scan of the
  surrendered slip).

A company's approved, RFID-tagged representatives feed straight into the
election roll — membership management is the roll's source of truth
alongside the older Excel/Zoho import path (see "Membership management"
below).

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
operator account per counter + one exit-desk operator account per EVM
confirmation desk:

```bash
mysql -u root -e "CREATE DATABASE fkcci_election CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
php spark migrate --all
php spark db:seed InitialUsersSeeder
```

`InitialUsersSeeder` creates `admin` (12-char random password),
`counter1`–`counter10` (6-digit random PIN), and `exit1`–`exit4` (6-digit
random PIN, one per exit desk — see "EVM vote confirmation" below for why
4 and how to change it), each printed to the console **once** — copy them
down immediately, they aren't stored anywhere in plaintext. Re-running the
seeder is safe; it skips any username that already exists rather than
resetting it.

For a quick demo with realistic test data (10 companies, 2 members each,
matching the HTML prototype's dataset), also run:

```bash
php spark db:seed MasterDataSeeder
```

Run it:

```bash
php spark serve
```

Add a daily cron job for cleanup of abandoned preview uploads (an admin
uploads an Excel/photo zip for preview, then never confirms it — nothing
else removes that temp file):

```
0 3 * * * cd /path/to/app && php spark uploads:clean-tmp
```

## Architecture

See [`prototype/README.md`](prototype/README.md#suggested-mysql-schema-starting-point-for-the-ci4-build)
for the original schema sketch and the design decisions behind it (why
one-vote-per-company is enforced with a DB-level generated-column unique
index rather than only an app-level check, why photos are a separate optional
upload rather than an Excel column, etc). The sections below describe what
this build actually implements — read the prototype README first for *why*.

### Roles & auth

Three roles, one `users` table, session-based auth (no third-party auth
package — the surface is deliberately small):

- **admin** — full access: master data import, membership applications,
  voter log, dashboard, and a backup exit-scan station.
- **operator** — assigned to exactly one counter (`users.assigned_counter`),
  can only use the counter scan/issue screen and reprint slips.
- **exit_operator** — assigned to exactly one exit desk
  (`users.assigned_exit_desk`), can only use the exit-desk QR scan screen
  (`/exit`) to confirm EVM votes.

Membership self-registration (`/membership/...`) is public, no auth —
applicants are identified only by a generated `application_ref`, the same
"no login, just a reference/serial" pattern the election side uses for
voting slips.

### Hardware integration

- **Zebra RFID reader**: configured in USB/Bluetooth **keyboard-wedge (HID)
  mode** — it types the tag ID as keystrokes into whatever input has focus,
  followed by Enter. No SDK/driver integration on our side; the counter
  screen just keeps `#rfidInput` focused at all times (refocus on any click,
  on window refocus, and a periodic safety-net check) and listens for Enter.
  If a reader ships configured for a different suffix (Tab instead of
  Enter) or a prefix character, that's a one-line change in
  `app/Views/counter/index.php`'s keydown handler.
- **Thermal slip printer**: the browser's native print dialog (`window.print()`
  on issue, or the "Print / Choose Printer" button on `/slip/{serial}`) is
  the printer integration — whatever printer is set up in the OS (thermal,
  laser, or "save as PDF") just works, no vendor SDK needed. The print CSS
  (`@media print` in `assets/css/app.css`) is tuned for an 80mm thermal
  roll (`@page { size: 80mm auto; }`, slip width capped at 74mm, QR sized
  for a small paper width) — for a different roll width, adjust those two
  numbers.

### Membership management

The paper "Application for Membership" form and the sample PVC ID card
(FKCCI logo, bilingual org name, category badge + membership no.,
company/representative name, nature-of-business line, President/Secretary
General signature lines) are what this whole module is built against —
every field on the online form and every line on the printed card maps
directly to one of those two source documents.

**1. Public self-registration (`/membership/apply`)** — one page, all of
Section 1–8 of the paper form: organisation details, nature of business
(with Small/Large-Medium scale where it applies), 2 or 3 representatives
(the form's own rule: small category gets 2, Large/Medium and Association/
Chamber members get up to 3 — enforced both in the UI and server-side)
with photo upload, financial/registration numbers (GSTIN, PAN, MSME,
IE Code, etc.), and the same 5 supporting-document uploads the form lists
("as applicable"). The fee due is computed server-side by
`FeeScheduleService`, which encodes the exact printed fee table (Small/
Large × Manufacture/Trade/Service/Profession/District/Association ×
Ordinary/Patron, plus flat Gold/Platinum patron tiers) rather than
recomputing GST from scratch — every figure is copied verbatim from the
form, since these are real money amounts.

**2. Payment (`/membership/pay/{ref}`)** — same simulated-until-configured
pattern as the Zoho integration (see below): `PaymentGatewayInterface` +
`PaymentGatewayFactory`, with `SimulatedPaymentGateway` (no network call,
marks paid immediately) as the default and `LiveRazorpayGateway` written
against Razorpay's documented Orders API but **never run against a real
Razorpay account** — there were no credentials available while building
this. To go live: set `paymentGateway.driver = live` and
`paymentGateway.razorpayKeyId`/`razorpayKeySecret` in `.env`, and wire
Razorpay's Checkout.js into `app/Views/membership/pay.php` (see that
file's comment). Nothing outside `PaymentGatewayFactory` needs to change.

**3. Two-stage approval (`/admin/membership`)** — mirrors the paper form's
own "For Office Use Only" block exactly: Present to Membership Committee →
Recommend/Reject → Present to Managing Committee → Approve/Reject. Each
stage records who acted and when (`membership_applications` table). A
rejected application stops there; an approved one moves to:

**4. Registration & RFID issuance** — "Entered in Membership Register...
Identity Card No." from the paper form becomes one admin action
(`POST /admin/membership/{id}/register`) that assigns the company's
`membership_no` (e.g. `SSO-1326` — see the numbering scheme note below)
and each representative's `member_id` (`{membership_no}-A`/`-B`/`-C`) in
one shot. RFID tags are assigned **separately, per representative**
(tap-to-assign, same keyboard-wedge pattern as the election's RFID
reader), since members collect their physical card individually and that
may not all happen the same day as registration.

**5. PVC ID card (`/admin/membership/card/{member_id}`)** — printable at
the standard CR-80 size (86mm × 54mm), styled after the sample card:
logo, bilingual org name, category badge + membership no., company name,
representative name, nature-of-business/scale line, and blank signature
lines for the Secretary General, President (names configured in
`app/Config/Membership.php` — update when office bearers change), and the
member themself. Browser `window.print()` to a PVC card printer, same
no-SDK approach as the election's thermal slip — only becomes available
once a representative has both a `member_id` and an RFID tag.

**Data model decisions worth knowing:**

- `companies` grew the full organisation profile (address, nature of
  business, financials, registration numbers) rather than a new table,
  since it's already the entity the election system uses — membership is
  now this app's source of truth for that data, not a separate system
  bolted on the side.
- Workflow state lives in a separate `membership_applications` table, not
  on `companies` — an application is a point-in-time process with its own
  actors/timestamps; `companies` is the durable record. `companies.membership_status`
  defaults `'active'` so every company row from before this feature
  (Excel/Zoho import) is unaffected.
- `members.member_id`/`rfid_tag` are nullable: a representative is
  captured at *application* time (name, designation, photo) but only
  numbered/tagged at *approval*, matching the paper form's own sequence.
  The Excel/Zoho import path still guarantees both are non-empty itself
  before ever inserting — this only widens what self-registration is
  allowed to leave blank initially.
- `members.is_election_rep` caps voting at 2 representatives per company
  regardless of how many the membership carries (associations/large-scale
  members may register up to 3) — `MemberModel::findByRfid()` (the hot
  path every counter scan hits) filters on it, so a 3rd representative's
  tag can never cast the company's one vote in place of a designated rep.
- `companies.membership_no`'s format (scale-initial + nature-initial +
  category-initial + sequence, e.g. "SSO-1326" = Small+Service+Ordinary)
  is **inferred from the one sample card available**, not confirmed
  against FKCCI's real numbering register — same caveat as the Zoho
  field-name mapping below. See `FeeScheduleService::prefixFor()`.

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

### EVM vote confirmation (`/exit`, exit_operator/admin)

Getting a voting slip is not the same as casting a ballot — a slip only
proves eligibility. To measure real turnout, members surrender their slip
after voting at the EVM, and an exit-desk operator (or admin, as a backup
station) scans its QR here to confirm that specific slip turned into a
cast vote.

- **Scanning is camera-based**, not the RFID keyboard-wedge reader used at
  the issuing counters: the browser's own camera (`getUserMedia`) feeds a
  live decode loop built on [jsQR](https://github.com/cozmo/jsQR) (vendored
  at `public/assets/js/vendor/jsQR.js`, Apache-2.0 — no CDN dependency, no
  network call). A manual "enter slip number" fallback sits right below the
  camera view for when a camera isn't available or a scan won't read.
- **Camera access requires a secure context** — `https://` in production,
  or `http://localhost` for local dev (which is why this works out of the
  box with `php spark serve` on your own machine but needs real TLS once
  deployed).
- **Data model**: confirmation is `votes.voted_at` / `voted_by` /
  `exit_desk_no` (see migration `AddEvmVoteConfirmationTracking`) —
  deliberately *not* a third `votes.status` value. `status` alone drives
  the `active_company_id` generated column that enforces one-issued-slip-
  per-company (see `CreateVotesTableMigration`); a slip must stay
  `'issued'` after EVM confirmation, or a second slip could be issued for
  that company once the first is marked confirmed. Slip validity and EVM
  confirmation are independent facts, so they're independent columns —
  voiding a slip and confirming a slip don't interact with each other's
  state.
- **Invalid scans are blocked, not silently ignored**: a voided slip shows
  why and is refused (send the member to the admin desk); a slip already
  confirmed shows when/where it was first confirmed and records nothing
  new; an unrecognized QR says so. None of these write to the database.
- **How many exit desks**: nothing in the original brief fixes this number
  (unlike the 10 issuing counters, which is a hard given) — `InitialUsersSeeder`
  seeds 4 as a starting assumption (`exit1`–`exit4`). Change
  `EXIT_DESK_COUNT` in that seeder and rerun `php spark db:seed
  InitialUsersSeeder` to add more; existing accounts are left alone.
- **Admin dashboard** shows issued-vs-confirmed counts and an EVM turnout
  percentage; the **voter log** has a "Confirmed at EVM?" filter, an EVM
  column, and the CSV export includes `voted_at`/`exit_desk_no`.

### QR verification (`/verify/{serial}`, public, no auth)

Scanning the slip's QR code opens this page showing the member's photo (if
on file), name, company, and whether the slip is valid/void. No auth — this
is meant to be scanned by hall-entry staff on their own phones.

### Admin (`/admin/...`, admin role only)

- **Master data** — Excel import (`phpoffice/phpspreadsheet`) for
  RFID/Member ID/Name/Company/Designation/Mobile/Email, *or* a Zoho CRM sync
  as an alternative source (see below — simulated until real credentials
  are configured), plus a *separate*, optional bulk photo upload (`.zip`,
  matched to members by filename == Member ID). None of Excel import, Zoho
  sync, or voting requires a photo to exist.
- **Voter log** — search/filter by counter/status/EVM confirmation, void a
  wrongly-issued slip with a reason, CSV export.
- **Dashboard** — turnout stats, votes per counter, companies pending, EVM
  confirmation turnout.
- **Exit Scan (backup station)** — same camera QR scanner as an exit desk
  operator, for when the dedicated exit desks need backup capacity.

## Branding

Every page shows the FKCCI seal top-left and a "Powered by: World Vision
Softek" footer (`app/Views/partials/footer.php`). The logo asset is
`public/assets/img/fkcci-mark.png` — cropped from the supplied logo (which
has an "Estd. 1916" caption beneath the seal, not usable at the ~40px header
size) down to just the circular emblem, then compressed with pngquant since
it loads on every page. To swap it for an updated logo file later, replace
that one PNG — every header pulls from the same `.brand-mark` CSS class.

## Zoho CRM sync (alternative to the Excel import)

Admin → Master Data → Step 1 · Option B lets an admin pull member data from
Zoho CRM instead of uploading an Excel file — same preview-before-commit
flow, same validation, writes to the same `members`/`companies` tables. One
Zoho record is assumed to be a company's membership with **both** designated
contact persons on it (matching how this system needs two independent
`members` rows per company); confirm that's actually how your Zoho module is
laid out once you have access to it.

**This ships in `simulated` mode** — `app/Libraries/Zoho/SimulatedZohoMembersClient.php`
returns fixed sample data, no network call, so the whole import UI can be
demoed and tested without Zoho credentials. `LiveZohoMembersClient.php` is
written against Zoho CRM's documented REST API v2 (OAuth2 refresh-token
flow, paginated `GET /crm/v2/{module}`) but has **never been run against a
real Zoho org** — there were no credentials available while building this.

**To go live**, no code changes are needed — only configuration:

1. Set `zoho.driver = live` in `.env`
2. Fill in `zoho.clientId` / `clientSecret` / `refreshToken` /
   `accountsDomain` / `apiDomain` (region-specific — India is
   `accounts.zoho.in` / `www.zohoapis.in`, see Zoho's multi-DC docs for
   other regions)
3. **Confirm `app/Config/Zoho.php`'s `$module` and `$fieldMap` against your
   org's actual field API names** (Setup → Customization → Modules and
   Fields — the API name, not the display label). The names currently in
   there (`Membership_ID`, `Contact_Person_1`, `RFID_Tag_ID_1`, etc.) are
   placeholders based on the field list you gave, not verified against a
   real module.

Everything else — the controller, the preview/commit UI, validation — talks
only to `ZohoMembersClientInterface`, never to either implementation
directly, via `ZohoClientFactory::make()`. That's what makes step 1 the only
step.

## Directory notes

- `prototype/` — the static HTML/JS prototype (kept for reference and
  side-by-side UX comparison; not served by the app).
- `app/Controllers/Counter`, `app/Controllers/Exit`, `app/Controllers/Membership`,
  `app/Controllers/Admin`, `app/Controllers/Auth` — route handlers by area.
- `app/Libraries/Membership`, `app/Libraries/Payment`, `app/Libraries/Zoho` —
  the fee schedule and the two swappable-integration pairs
  (interface + factory + simulated/live implementations).
- `app/Database/Migrations` — schema. `app/Database/Seeds` — initial users.
- `public/assets/js/vendor/` — vendored third-party JS (currently just
  jsQR, for exit-desk camera QR scanning) — no CDN dependency, no build step.
- `writable/uploads/photos/` — uploaded member/representative photos
  (gitignored). `writable/uploads/membership_docs/{application_id}/` —
  uploaded supporting documents, admin-only download (gitignored, never
  served publicly, unlike photos).
