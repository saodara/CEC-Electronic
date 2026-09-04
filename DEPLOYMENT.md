# Database & Deployment

How this app's database and production deployment are actually wired up: Render (Docker) for hosting, Neon for Postgres. Table-by-table schema documentation lives in `README.md` ("Database Schema" section) — this file covers how the pieces connect and how to operate/debug them.

## Database (Neon Postgres)

- **Provider:** [Neon](https://neon.tech) serverless Postgres. One project, one branch (`main`), database name `neondb`.
- **Connection:** the app connects over TLS (`DB_SSLMODE=require`) to Neon's pooled endpoint. Credentials live only in Render's `APP_KEY` environment group (see below) and your local `.env` — never committed.
- **Driver:** `pgsql` via PDO. Requires the `pdo_pgsql` PHP extension, which the `Dockerfile` installs explicitly (`docker-php-ext-install pdo_pgsql`).
- **Schema:** managed entirely through Laravel migrations in `database/migrations/`. There is no manual schema — every table is created/altered by a migration. See `README.md` for the current table-by-table breakdown (`users`, `products`, `orders`, `suppliers`, delivery tables, etc.).
- **Seeding:** `database/seeders/DatabaseSeeder.php` creates/updates the admin user from `ADMIN_EMAIL`/`ADMIN_PASSWORD` (via `updateOrCreate`, so it's safe to re-run) and seeds sample products/suppliers. It runs automatically on first container boot only (see `docker/entrypoint.sh` — gated by a `storage/.docker_seeded` flag file) so it won't re-run on every deploy and duplicate data.
- **Local dev:** same Neon database as production by default (see `docker-compose.yml`, which reads `DB_HOST`/`DB_USERNAME`/`DB_PASSWORD` from your local `.env`). There's no separate local Postgres container — you're developing against the real Neon instance, so be mindful before running destructive artisan commands locally.

### Making schema changes

```bash
php artisan make:migration add_something_to_products_table
php artisan migrate          # local
```
Migrations run automatically on every deploy (`docker/entrypoint.sh` runs `php artisan migrate --force` on container boot), so you don't need to run them manually on Render — just push to `main` and redeploy.

## Deployment (Render, Docker)

- **Service:** `cec-electronic`, a Render Web Service running `Dockerfile` directly (`runtime: docker` in `render.yaml`). Free plan.
- **How a deploy happens:** push to `main` on GitHub, then trigger it from the Render dashboard (**Manual Deploy**) — this repo's auto-deploy-on-push has not been reliably observed, so always check the **Deploys** tab after pushing to confirm a new deploy actually started on the right commit.
- **Build:** `Dockerfile` installs PHP 8.3 + extensions (incl. `pdo_pgsql`), Composer, Node 22; runs `composer install --no-dev`, `npm ci && npm run build`; copies in `docker/nginx.conf` and `docker/entrypoint.sh`.
- **Boot sequence** (`docker/entrypoint.sh`, runs as root on every container start):
  1. Create `.env` from `.env.example` if missing (real config still comes from real env vars, which take precedence — the file is just a fallback Laravel expects to exist).
  2. Generate `APP_KEY` if not already set via the environment.
  3. Fix `storage`/`bootstrap/cache` ownership (`www-data`) and permissions.
  4. Wait (bounded, loud) for the database to become reachable — fails fast with a clear error rather than hanging if `DB_HOST`/etc. are wrong.
  5. Run migrations (`--force`).
  6. Seed the database, first boot only.
  7. **Pre-cache views/routes/config** (`view:cache`, `route:cache`, `config:cache`) while still root, then re-chown. This exists because `php-fpm` (running as `www-data`) cannot write to `storage/framework/views` at request time on Render's filesystem — without pre-caching, the *first* request to any page throws a fatal 500 (`tempnam(): file created in the system's temporary directory`) while Blade tries to compile a view on the fly.
  8. Start `php-fpm`, then `nginx` in the foreground.

### Required environment variables

Set these on the Render service (either directly, or via a linked Environment Group — see the note on that below). Real values are never in this repo; check your local `.env` or the Render dashboard.

| Variable | Notes |
| --- | --- |
| `APP_KEY` | `base64:...` — generate with `php artisan key:generate --show` |
| `APP_URL` | e.g. `https://cec-electronic.onrender.com` — only cosmetic now (see HTTPS note below), but still used for absolute links in emails/CLI output |
| `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` | From your Neon project's connection details |
| `DB_CONNECTION`, `DB_PORT`, `DB_DATABASE`, `DB_SSLMODE` | Static — `pgsql` / `5432` / `neondb` / `require` |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Login for `/admin/login`; seeded automatically, see above |
| `BAKONG_RELAY_URL`, `BAKONG_ACCOUNT_ID` | Bakong/KHQR payment integration — currently **disabled** in the checkout UI (option removed from `resources/views/checkout/create.blade.php`), so these aren't required for the app to function |
| `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `FILESYSTEM_DISK`, `LOG_CHANNEL`, `LOG_LEVEL` | Static production values — see `render.yaml` for the exact values each one should be |

**A note on the Render Environment Group named `APP_KEY`:** this project has one, holding all of the above. In practice, the "link this group to the service" dashboard flow has been unreliable — variables have silently failed to save or disappeared from the linked list. If a deploy fails on a missing/empty variable, don't assume the group is linked correctly: check the **service's own Environment tab** directly, and if a variable is missing, add it there individually rather than relying on the group link. To verify ground truth (not just the dashboard, which has shown stale/misleading masked values), use the **Shell** tab on the service and run:
```
printenv | grep -E "APP_URL|DB_HOST|DB_USERNAME|DB_PASSWORD|APP_KEY"
```

### HTTPS / reverse proxy handling

Render terminates TLS at its edge and forwards plain HTTP to the container. Two things make the app generate correct `https://` URLs (login/register/checkout forms, etc.) without depending on `APP_URL` being perfectly set:
- `docker/nginx.conf` explicitly forwards the real `Host` header (`fastcgi_param HTTP_HOST $http_host`) instead of Debian's default, which strips the port.
- `bootstrap/app.php` trusts the proxy (`$middleware->trustProxies(at: '*')`), so Laravel honors Render's `X-Forwarded-Proto: https` header.

If you ever see a browser "this form is not secure" warning in production, it means one of these two got undone — it is **not** something to fix by re-typing `APP_URL` again.

### Troubleshooting checklist

If a deploy fails or the site errors, check in this order:
1. **Deploys tab** — did a new deploy actually start on the commit you expect?
2. **Logs tab** — does the boot sequence (above) complete, ending in `App is ready at http://localhost:8080`? The most common failure is the DB-wait loop timing out — that always means an env var is missing/wrong, not a code bug.
3. **Shell tab** — `printenv` to confirm what the container actually sees, since the dashboard's masked variable list has been unreliable for this project.
4. **Local repro** — `docker compose up -d --build` reproduces the exact same image/entrypoint locally against the same Neon database, which is the fastest way to confirm whether an issue is code or Render-environment-specific.
