# HRP

Laravel API (`backend/`) + Vue 3 SPA with Vuetify (`frontend/`), MySQL, all via Docker (Laravel Sail).

## Run

```sh
cd backend
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

- Frontend: http://localhost:5173 (Vite dev server, proxies `/api` to Laravel)
- API: http://localhost/api/ping
- MySQL: localhost:3306 (user `sail`, password `password`, db `laravel`)

First run only: `cp backend/.env.example backend/.env` is already done; if starting fresh, `composer install` in `backend/` before Sail exists.
