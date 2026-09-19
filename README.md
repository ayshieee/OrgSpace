# OrgSpace

OrgSpace is a web platform for running student and campus organizations — academic clubs, student councils, fraternities/sororities, sports clubs, performing-arts ensembles, and community-service groups. Each organization gets its own configurable hub: a member roster, role-based permissions, and a set of feature modules it can turn on or off (announcements, events, attendance, file storage, a music library, and dues/finance tracking).

The platform is multi-tenant: a single OrgSpace installation hosts many independent organizations, and a user can belong to more than one.

## How it works

**Onboarding.** A new organization is created through a guided, multi-step wizard: set up the organization profile, define roles, import or invite members, choose which feature modules to enable, then review and activate.

**Roles & permissions.** Every organization defines its own roles, each holding a set of permissions (manage settings, manage roles, manage roster, manage events, manage attendance, manage announcements, manage files, manage finance, manage the music library, view reports, manage modules, view roster). Three starting templates are provided:
- **Adviser** — full access to everything.
- **Officer** — day-to-day operational permissions.
- **Member** — read-only roster access.

Permissions are enforced at the route level, and modules that an organization hasn't enabled are inaccessible even to members with permission, until an admin turns them on.

**Feature modules.**
- **Member Management** — the roster and core membership data; always on.
- **Announcements** — posts with attachments, threaded comments, emoji reactions, and read receipts.
- **Events & Calendar** — event scheduling with RSVPs and checklists.
- **Attendance Tracking** — QR-code based check-in sessions, with phone scanning and manual excuse handling.
- **Secure Files** — folders and files with per-file access tiers, watermarking, and optional Microsoft 365/Azure AD "Open in Office" integration.
- **Music Library** — sheet-music storage with on-canvas annotations and favorites, aimed at performing-arts organizations.
- **Finance & Dues** — budget and fee tracking (module scaffolding in place).

Alongside the modules, every user has a **join flow** (join by invite code or request-to-join with officer approval), a **notification center**, and a **profile** (avatar, education history, and a forced password-change flow for admin-provisioned accounts).

## Tech stack

- **Backend:** Laravel 12 (PHP ^8.2)
- **Frontend:** Inertia.js v2 + Vue 3, Tailwind CSS 3, Vite 7
- **Auth:** Laravel Breeze (Inertia/Vue stack), extended with a forced password-change flow
- **Database:** SQLite by default (configurable to any Laravel-supported driver)
- **Other notable packages:** Tiptap (rich text for announcements), `qrcode`/`jsqr` (attendance QR flow), `maatwebsite/excel` (roster import/export), `resend/resend-php` (transactional email), `setasign/fpdi-fpdf` (PDF watermarking), Ziggy (Laravel routes in JS)

## Getting started

### Requirements
- PHP 8.2+
- Composer
- Node.js 18+ and npm

### Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using the default SQLite driver
php artisan migrate
npm run build   # or `npm run dev` for hot-reloading during development
php artisan serve
```

Then visit the URL printed by `artisan serve` (defaults to `http://127.0.0.1:8000`).

### Configuration notes
- Feature module definitions live in `config/modules.php`; the permission catalog lives in `config/permissions.php`; organization types live in `config/organization.php`.
- Mail defaults to SMTP; a Resend API key can be used instead by switching `MAIL_MAILER`.
- Queue, cache, and session all default to the `database` driver, so no extra services (Redis, Memcached) are required to run locally.

## Project structure

- `app/Http/Controllers/{Auth,Onboarding,Membership,Organization,Profile}` — controllers grouped by domain area.
- `app/Models` — `Organization`, `OrganizationMember`, `Role`/`RolePermission`, `OrgFeature`, `Announcement` (+ comments/reactions/attachments), `Event`/`EventRsvp`, `AttendanceSession`/`AttendanceRecord`, `OrgFile`, `MusicEntry`, and related models.
- `resources/js/Pages` — Inertia page components, mirroring the route structure (`Onboarding/*`, `Organizations/{Announcements,Events,Files,Music,Attendance,Members}/*`, `Profile`, `Auth`, `Notifications`).
- `resources/js/Components` — shared UI components, grouped by feature area.
- `database/migrations` — full schema history for organizations, roles, and every feature module.
