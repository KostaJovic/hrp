# Home Resource Planner (HRP)

Personal household ERP. Linear project: Home-Management-System (team HOM).
Architecture reasoning lives in the Linear document "Datenmodell & Architektur (HOM-5)".

## Layout & commands

- `backend/` — Laravel 13 API (PHP 8.3, Sanctum, MySQL via Sail on port 8080)
- `frontend/` — Vue 3 + Vuetify 4 SPA (Vite on 5173, proxies `/api` and `/sanctum` to the backend)
- Start: `cd backend && ./vendor/bin/sail up -d` (starts backend + frontend + mysql)
- Tests: `cd backend && php artisan test` — runs on SQLite `:memory:`, no Sail needed
- Lint: `cd backend && ./vendor/bin/pint --dirty`
- Seed demo data: `sail artisan migrate:fresh --seed` (login: kosta.j@icloud.com / password)

## Conventions (decided in HOM-5 — follow them)

- **Language**: code, API routes, DB columns in English; UI labels in German.
- **API**: everything under `/api/v1`, `auth:sanctum` protected. apiResource controllers +
  FormRequests (validation only there) + Eloquent API Resources (`{data}` shape).
  Filters are flat query params; sort is `sort=-created_at` style via allowlist;
  pagination `per_page` max 100.
- **Auth**: Sanctum SPA cookie flow (`statefulApi()`), single user. No roles yet (HOM-13).
- **Money**: integer cents in `*_cents` columns, EUR. Never floats.
- **Enums**: DB `string` columns + string-backed PHP enums in `app/Enums/`, cast in models.
- **FK delete behavior is explicit**: `nullOnDelete` for optional references,
  `cascadeOnDelete` for owned children (maintenance → item), `restrictOnDelete` for the
  location hierarchy. Soft deletes only on `items` and `projects`.
- **Recurrence**: `recurrence_interval` + `recurrence_unit` columns (`RecurrenceUnit::addTo()`),
  materialized on completion. No RRULE.
- **Polymorphics**: morph map is enforced (`AppServiceProvider`) — short names (`item`, `task`,
  `expense`, `maintenance_log`, `project`) in the DB. Tags via `HasTags`, files via
  `HasDocuments` + one `documents` table (photos included, `kind` column).
- **Files**: private disk only, served through the authenticated download route.
- **Search**: LIKE + structured filters (portable across MySQL/SQLite). Scout only if it hurts.
- **Tests**: feature test per endpoint (401 / happy path / 422 / FK behavior) + targeted logic
  tests. Guards memoize users within a test — `$this->app['auth']->forgetGuards()` between
  auth-state changes.
- **Frontend**: axios instance in `src/api/http.js` + thin per-resource modules; Pinia only for
  the auth store, module data stays in component state. `AppLayout.vue` = drawer (desktop) +
  bottom nav (mobile via `useDisplay()`).

## Process

Mirror subtasks as Linear sub-issues and update their status as work progresses; document
decisions (with the *why*) in Linear comments on the issue.
