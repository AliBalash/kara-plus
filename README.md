# Kara Plus

Kara Plus is a Laravel-based operations platform for managing the end‑to‑end car‑rental workflow: rental requests, vehicles, customers, payments, approvals, and operational tasks. It ships with a production Docker stack and a single CI/CD workflow that runs tests and deploys on `master`.

---

## What This Project Contains

**Core workflows (from routes & Livewire pages)**
- Rental requests: create/edit, detail, history, payment, reserved, pickup/return docs, inspections, approvals
- Cars & brands: list/detail/create/edit
- Customers: list/detail/history/debts/documents
- Payments: confirmation, processed list, edit
- Cashier dashboard
- Insurance management
- Agents & location costs
- Users & roles (Spatie Permission)
- Reports (user request stats)

**Stack**
- PHP 8.2, Laravel 11, Livewire 3
- MySQL 8, Redis
- Vite + Tailwind CSS
- Docker + Nginx
- GitHub Actions (CI/CD)

---

## Local Setup (Docker — Recommended)

```bash
# Build and start services
docker compose --env-file .env.docker up -d --build

# App initialization
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
```

**Ports (from .env.docker)**
- HTTP: `18000`
- HTTPS: `18001`
- phpMyAdmin: `18002`

**Notes**
- App config is in `.env`; Docker config in `.env.docker`.
- Ensure DB credentials in `.env` match `.env.docker`.

---

## Local Setup (No Docker)

```bash
composer install
npm install

php artisan key:generate
php artisan migrate

php artisan serve
npm run dev
```

---

## Running Tests

```bash
php artisan test
# or
vendor/bin/phpunit
```

---

## CI/CD

Workflow file: `.github/workflows/ci-cd.yml`

Behavior:
- **Pull Request** → run tests only
- **Push to master** → run tests, then deploy if successful

---

## Production Deployment

Deploy script: `deploy.sh`

Main steps:
1. Fetch + hard reset to `origin/master`
2. Build & start Docker services
3. Install Composer deps (no-dev)
4. Create storage link
5. Run migrations
6. Cache config/routes/views
7. Restart queue workers

> The deploy script runs on the server and uses `git reset --hard`.

---

## Database Backups

Backup script: `scripts/backup-mysql-to-gdrive.sh`

Features:
- Dumps MySQL from the container, compresses output
- Uploads to Google Drive via `rclone`
- Applies retention (remote & local)

Required config:
- `.env` (DB credentials)
- `/etc/kara-plus/backup.env` (rclone + retention settings)

---

## Project Structure (Key Paths)
- `app/` application logic (Livewire, Models, Controllers)
- `routes/` web routes
- `resources/` views and styles
- `database/` migrations and factories
- `docker/` Docker and Nginx configuration
- `scripts/` operational scripts

---

## Security Notes
- Keep secrets only in env files.
- Do not commit credentials.
- Restrict access to the self-hosted runner and server.
