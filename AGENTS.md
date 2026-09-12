# AGENTS.md

## Cursor Cloud specific instructions

### Project overview
This is a Persian/Iranian CRM & ERP panel ("Setaregan CRM") built with Laravel 12, Livewire 4, Flux UI Pro, and Tailwind CSS 4. See `.junie/guidelines.md` and user rules for coding conventions.

### Prerequisites
- **PHP 8.4** (from `ppa:ondrej/php`) with extensions: mbstring, xml, curl, zip, gd, intl, sqlite3, mysql, bcmath, imagick.
- **Composer** (system-wide at `/usr/local/bin/composer`).
- **Node.js 22** (via nvm, pre-installed).
- **Flux UI Pro license**: `composer install` requires HTTP-basic auth for `composer.fluxui.dev`. Without credentials, a stub is used (see below).

### Flux UI Pro license
The `livewire/flux-pro` package requires HTTP-basic auth for `composer.fluxui.dev`. The secrets `FLUX_LICENSE_EMAIL` and `FLUX_LICENSE_KEY` must be configured in the Cursor Cloud environment. The update script runs `composer config http-basic.composer.fluxui.dev` with these values before `composer install`. If these secrets are missing, the update script falls back to a local stub that lets the app boot but renders Flux Pro components without full styling/JS.

### Locale
Set `APP_LOCALE=fa` and `APP_FALLBACK_LOCALE=fa` in `.env` for proper Farsi translations and RTL layout.

### Running the dev servers
Use `composer dev` (defined in `composer.json` scripts) to start all services concurrently:
- `php artisan serve` (HTTP on :8000)
- `php artisan queue:listen` (background jobs)
- `php artisan pail` (log viewer)
- `npm run dev` (Vite on :5173)

Or run them separately in tmux sessions.

### Database
- Default: **SQLite** (`database/database.sqlite`). The `.env.example` defaults to SQLite.
- Tests: **SQLite in-memory** (configured in `phpunit.xml`).
- Some migrations target a `voip` connection (external MySQL). These fail locally and must be skipped. Run `php artisan migrate --force --graceful` and then run remaining non-voip migrations individually with `--path`.

### Running tests
```
php artisan test
```
The default `ExampleTest` feature test returns 302 (redirect to login) instead of 200; this is a pre-existing issue.

### Linting
```
./vendor/bin/pint --test   # check only
./vendor/bin/pint           # auto-fix
```

### External services (optional, not available locally)
- **Sepidar ERP** (SQL Server) – accounting data
- **Issabel VoIP** (MySQL) – call records
- **Bale Messenger Bot** – notifications
- **SMS Gateway** – OTP codes
These require network access to production infrastructure and are not needed for core development.
