# Commander

Internal Laravel command center that ships an authenticated dashboard for running sensitive maintenance tasks (artisan, SQL, queues, git, file edits, DB backups, etc.) from within an existing application.

## Features
- Inertia-powered dashboard surfaced at `/command` (behind `web`, `auth`, `verified` middleware by default).
- Secure master-password gate + checkbox confirmation before executing any destructive action.
- Tools for artisan commands, environment variable editing, SQL queries, log tailing, whitelisted file editing, git status/fetch/pull, queue maintenance, system info, and backup management.
- MySQL backup/restore pipeline that prefers `mysqldump` and falls back to a PHP-based exporter when the binary is unavailable.
- Configurable file roots, git defaults, and backup storage paths via `config/command-center.php`.
- Publishable Vue + Inertia scaffolding so the UI can be tailored to your stack.

## Installation
1. Require the package:
   ```bash
   composer require madassdev/commander
   ```
2. Publish the configuration (optional but recommended):
   ```bash
   php artisan vendor:publish --provider="Madassdev\Commander\CommandCenterServiceProvider" --tag="command-center-config"
   ```
3. Publish the Inertia scaffolding if you want to customize the Vue layout, components, or pages:
   ```bash
   php artisan vendor:publish --provider="Madassdev\Commander\CommandCenterServiceProvider" --tag="command-center-inertia"
   ```
4. Run `php artisan route:clear` to ensure the package routes are discovered.

The service provider is auto-discovered by Laravel, so no manual registration is required.

## Configuration
The published `config/command-center.php` exposes the knobs you can tune:

| Key | Description |
| --- | --- |
| `master_password` | Reads from `COMMAND_MASTER_PASSWORD` (or `ADMIN_TOOL_PASSWORD`). Required; every action validates this secret. |
| `backup_path` | Directory for database backups (defaults to `storage_path('command-backups')`). |
| `mysql_dump_binary` | Path to `mysqldump`. Leave as `mysqldump` if it’s on your `$PATH`. |
| `file_roots` | Whitelist of directories/files that can be edited from the UI. Ship with config, routes, resources, app, and `.env`. |
| `git.default_branch` | Default branch used when pulling via the Git tool. Defaults to `COMMAND_GIT_BRANCH` or `main`. |
| `routes` | Control middleware, prefix, and route name prefix (defaults: middleware `['web','auth','verified']`, prefix `command`, name `command.`). |

Set at least the master password in your `.env`:
```dotenv
COMMAND_MASTER_PASSWORD=super-secret-passphrase
# optional overrides
COMMAND_BACKUP_PATH=/var/backups/app
COMMAND_MYSQL_DUMP=/usr/local/bin/mysqldump
COMMAND_GIT_BRANCH=production
```

### Database requirements
Backups/restores currently target MySQL/MariaDB connections. The package tries `mysqldump` first (respecting `MYSQL_PWD`) and falls back to a PHP-based export/import if the binary fails.

### Route security
Routes load from `routes/command.php` under:

```php
Route::middleware(config('command-center.routes.middleware'))
    ->prefix(config('command-center.routes.prefix'))
    ->as(config('command-center.routes.name'))
    ->group(base_path('vendor/madassdev/commander/routes/command.php'));
```

Adjust the middleware/prefix/name defaults in `config/command-center.php` to suit your application. For more control you can still register your own group pointing to the vendor route file.

## Usage
Visit `/command` while authenticated. Each section enforces the master password + confirmation checkbox. Highlights:

- **Overview:** quick stats (env entries, file roots, git state).
- **Artisan:** run any artisan command, view exit codes/output.
- **Environment:** list/update `.env` entries safely.
- **SQL:** execute read or write statements; writes report affected rows.
- **Logs:** tail `storage/logs/laravel.log` (default 200 lines).
- **Files:** browse whitelisted directories, view/edit files.
- **Git:** run status, fetch, or pull (using configured branch).
- **Maintenance:** run cache/config clears, queue restarts, maintenance mode, scheduler tick.
- **System:** report PHP/Laravel/app environment metadata.
- **Backups:** create, download, restore, or delete database dumps.
- **Queues:** view queued/failed counts, see recent failures, flush pending/failed jobs.

Any failure flashes contextual error messages so you can guide the user.

## Front-end assets
Publishing the `command-center-inertia` tag will copy:
- `resources/js/Layouts/CommandLayout.vue`
- `resources/js/Components/command/*`
- `resources/js/Pages/Command/*`

Feel free to adapt the styling, add guardrails, or wire up additional telemetry. After editing, run your usual frontend build (e.g., `npm run build` or `npm run dev`).

## Local development tips
- Keep `COMMAND_MASTER_PASSWORD` out of source control; set via `.env` or injected secrets.
- Run `php artisan config:clear` after adjusting `command-center.php`.
- Ensure `storage/command-backups` (or your custom path) is writable by the web/php user.

Enjoy the ease of locking down production chores without cracking open SSH.
