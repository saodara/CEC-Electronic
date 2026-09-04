# Database & Deployment

How this app's database and production deployment actually work: Render (Docker) for hosting, Neon for Postgres. Every claim below is traced to the actual `Dockerfile`/`docker/entrypoint.sh`/`docker/nginx.conf`/`render.yaml`/`bootstrap/app.php`, plus real failure modes this project has hit and how they were actually diagnosed — not generic Laravel-deploy advice. Table-by-table schema documentation lives in `README.md`; app business logic lives in `PROJECT.md`.

## Architecture

```
GitHub (main) ──push──▶ Render Web Service "cec-electronic" ──▶ Docker build ──▶ container
                                    │                                              │
                         TLS terminated at Render's edge                          │
                         (plain HTTP forwarded to the container)                  │
                                    │                                              ▼
                              Browser ◀── https://cec-electronic.onrender.com   nginx :80
                                                                                    │
                                                                              php-fpm :9000
                                                                                    │
                                                                        Neon Postgres (TLS,
                                                                        DB_SSLMODE=require)
```

Local dev (`docker-compose.yml`) runs the **same image**, same `entrypoint.sh`, against the **same live Neon database** — there is no separate local Postgres container. This is convenient (identical behavior locally and in prod) and a real risk (destructive local `artisan` commands hit production data) — see "Local development" below.

## Database (Neon Postgres)

- **Provider:** [Neon](https://neon.tech) serverless Postgres, one project, database name `neondb`.
- **Driver:** `pgsql` via PDO. Requires the `pdo_pgsql` PHP extension — the `Dockerfile` installs it explicitly (`RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd intl zip xml`). If a deploy ever throws `PDOException: could not find driver`, this line is what to check first (has it been accidentally removed, or is the image actually rebuilding vs. using a stale cached layer).
- **TLS:** `DB_SSLMODE=require` — Neon requires TLS, this isn't optional.
- **Schema:** managed entirely through Laravel migrations in `database/migrations/`. No manual schema changes — every table is created/altered by a migration, run automatically on every deploy (see boot sequence below).
- **Seeding:** `database/seeders/DatabaseSeeder.php` creates/updates the admin user from `ADMIN_EMAIL`/`ADMIN_PASSWORD` (`User::updateOrCreate`, safe to re-run — changing these env vars and redeploying updates the existing admin row rather than erroring) and seeds sample catalog data. It's gated to run **once per container**, not once ever: `docker/entrypoint.sh` touches a flag file at `storage/.docker_seeded` after the first successful seed, and skips seeding on subsequent boots of the *same* container. Because Render's disk is ephemeral and a fresh container is built on every deploy, **seeding effectively runs again on every deploy** unless `storage/` is on a persistent disk. Check `ProductSeeder`/`SupplierDeliverySeeder` for `updateOrCreate` vs. plain `create()` before assuming re-seeding is harmless — a `create()`-based seeder would duplicate rows on every deploy.

### Making schema changes

```bash
php artisan make:migration add_something_to_products_table
php artisan migrate          # local — runs against the same Neon DB dev/prod share
```
No manual migration step is needed for Render — `entrypoint.sh` runs `php artisan migrate --force --no-interaction` on every container boot, before the app starts serving traffic.

## The Docker image

`Dockerfile`, single stage, `php:8.3-fpm` base:
1. System packages: `nginx`, `git`, `unzip`, and headers for the PHP extensions below, plus `libpq-dev` (Postgres client headers, needed to build `pdo_pgsql`).
2. PHP extensions: `pdo_pgsql mbstring exif pcntl bcmath gd intl zip xml`.
3. Composer binary copied in from the official `composer` image (no separate install step).
4. Node 22 installed via NodeSource, for the Vite frontend build.
5. `COPY . .` — the whole repo, **except** what `.dockerignore` excludes: `.git`, `node_modules`, `vendor`, `public/build`, and critically **`.env`/`.env.*`** (only `.env.example` is allowed through) — so no local secrets ever end up baked into the image, even accidentally. `storage/.docker_seeded` is also excluded, so a fresh image always re-seeds on first boot (see above).
6. `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, then `rm -rf node_modules` (Node isn't needed at runtime, only for the build step — keeps the final image smaller).
7. Creates the `storage/` subdirectories Laravel needs (`framework/{cache/data,sessions,views}`, `logs`, `app/{public,private}`) and `chown -R www-data:www-data storage bootstrap/cache` — this is the **build-time** ownership fix; `entrypoint.sh` repeats it at **boot time** too (see below), because build-time and boot-time are different filesystem states on Render (more on why under "Why the view-cache fix exists").
8. Copies `docker/nginx.conf` to `/etc/nginx/sites-available/default` and `docker/entrypoint.sh` to `/entrypoint.sh` (note: **not** under `/var/www/html` — this matters for local dev, see below).
9. `EXPOSE 80`, `ENTRYPOINT ["/entrypoint.sh"]`.

## Container boot sequence (`docker/entrypoint.sh`)

Runs as **root** on every container start, in this order:

1. **`.env` fallback:** if `/var/www/html/.env` doesn't exist, copy it from `.env.example`. This is a Laravel-expects-the-file-to-exist safety net, not the real config source — real environment variables (injected by Render, or by `docker-compose.yml` locally) always take precedence over anything in that file, since Laravel's dotenv loader only sets a variable if it isn't already present in the process environment.
2. **`APP_KEY` fallback:** generates one via `php artisan key:generate` only if it's genuinely unset in *both* the real environment and the `.env` file — avoids clobbering a key that's actually set.
3. **Dependency fallback:** `composer install`/`npm install && npm run build` only if `vendor/`/`public/build` are missing — normally a no-op since the image already has them from the build stage; this exists as a safety net for the local bind-mount scenario (see below), not for normal Render deploys.
4. **`storage:link`**.
5. **Ownership/permissions:** `chown -R www-data:www-data storage bootstrap/cache` + `chmod -R 775`.
6. **Wait for the database** — bounded (30 attempts, 2s apart = 60s max) and loud on purpose: a hanging or silently-failing DB wait would leave `php-fpm`/`nginx` never starting, which surfaces on Render only as an opaque "no open ports detected" with no indication why. Instead this fails fast with the actual PDO exception message and a reminder of which env vars to check. **This is the single most useful line for diagnosing a bad deploy** — read the exact error text here before guessing.
7. **Migrate:** `php artisan migrate --force --no-interaction`.
8. **Seed:** once per container, see database section above.
9. **Pre-cache views/routes/config**, then re-`chown`. See below — this step exists for a specific, non-obvious reason.
10. Start `php-fpm -D` (background), then `exec nginx -g 'daemon off;'` (foreground — this is what keeps the container alive).

### Why the view-cache step exists

`php-fpm` workers run as `www-data`, not root. On Render's filesystem, `www-data` cannot write to `storage/framework/views` at request time — the *first* request to render any new Blade view would fail with a fatal 500 (`tempnam(): file created in the system's temporary directory`, PHP's notice-level fallback-to-`/tmp` behavior escalated to fatal by Laravel's strict error handler). Step 9 runs `view:cache`/`route:cache`/`config:cache` **as root**, before dropping to `www-data` for request handling, so no Blade template ever needs to be compiled at request time in production — eliminating the write attempt entirely rather than trying to chase down exactly why `www-data` can't write there on this particular host.

This was diagnosed by reproducing it directly against a running container (`docker compose exec --user www-data app touch storage/framework/views/test` → `Permission denied`), not by guessing from the stack trace alone — the stack trace visible in the browser is misleading, because with `APP_DEBUG=true` Laravel tries to render its own detailed exception page as a Blade view too, which **also** fails the same way, so the debug page shows a secondary "can't render the error page" error instead of whatever the original problem was. If you ever see this exact `tempnam()` error again, check the Laravel log (`storage/logs/laravel.log`) directly rather than trusting the browser's rendered error page — logging doesn't go through Blade, so it isn't affected by the same failure.

## HTTPS / reverse proxy handling

Render terminates TLS at its edge and forwards **plain HTTP** to the container. Two gaps had to be closed for the app to generate correct `https://` URLs (login/register/checkout form actions, etc.) without depending on any environment variable being set correctly:

1. **Host header:** Debian's default `/etc/nginx/fastcgi_params` sets `HTTP_HOST` from nginx's `$host` variable rather than `$http_host` — a deliberate security hardening added upstream (see the comment block in that file) to avoid forwarding a raw, possibly-spoofed `Host:` header to the backend. The tradeoff is that `$host` **drops the port**, which broke local Docker's `localhost:8080 → container:80` port mapping (request-derived URLs would come out as `http://localhost/...`, missing `:8080`). Fixed in `docker/nginx.conf` by explicitly overriding it back to `$http_host` inside the PHP location block — an accepted tradeoff here since nothing in this app makes a security decision based on the `Host` header.
2. **Scheme:** nginx itself never terminates TLS (Render's edge does), so `$https`/`$scheme` as seen by nginx are always `http`/empty, regardless of what the actual client connection was. Render's edge sends `X-Forwarded-Proto: https` to communicate the real scheme — but Laravel ignores forwarded headers by default unless it explicitly trusts the proxy sending them. Fixed in `bootstrap/app.php` via `$middleware->trustProxies(at: '*')` (trusting all proxies is the standard/expected setting for PaaS platforms like Render, where you don't know the edge's IP in advance but the container is only reachable through it).

**An earlier fix attempt** (still visible in git history) forced every generated URL's root from `config('app.url')` via `URL::forceRootUrl()` in `AppServiceProvider`. That papered over both gaps at once, but made the *entire site's* HTTPS behavior depend on the `APP_URL` environment variable surviving Render's dashboard — which, in practice on this project, it repeatedly didn't (see "A note on environment variables" below). The current fix (nginx + trusted proxies) makes the app derive the correct scheme/host from the actual incoming request instead, so it no longer matters whether `APP_URL` is set at all. **Do not reintroduce `forceRootUrl`** without understanding this was a deliberate move away from it, not an oversight.

## Environment variables

Real values are never in this repo — check your local `.env` or the Render dashboard. `render.yaml` is the definitive list; static (non-secret) values are declared there directly, secrets are marked `sync: false` (meaning: Render won't auto-generate or sync a value, you must set it manually).

| Variable | Source | Notes |
| --- | --- | --- |
| `APP_KEY` | manual (`sync: false`) | `base64:...` — generate with `php artisan key:generate --show` |
| `APP_URL` | manual (`sync: false`) | e.g. `https://cec-electronic.onrender.com` — no longer load-bearing for HTTPS correctness (see above), but still used for absolute links outside a request context (emails, CLI/queue output) |
| `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` | manual (`sync: false`) | From your Neon project's connection details |
| `DB_CONNECTION`, `DB_PORT`, `DB_DATABASE`, `DB_SSLMODE` | static in `render.yaml` | `pgsql` / `5432` / `neondb` / `require` |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | manual (`sync: false`) | Login for `/admin/login`; seeded automatically, see Database section |
| `BAKONG_RELAY_URL`, `BAKONG_ACCOUNT_ID` | manual (`sync: false`) | Bakong/KHQR — currently **not required**, the payment option was removed from checkout (see `PROJECT.md`) |
| `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `FILESYSTEM_DISK`, `LOG_CHANNEL`, `LOG_LEVEL`, `BAKONG_MERCHANT_NAME`, `BAKONG_MERCHANT_CITY` | static in `render.yaml` | Exact values in `render.yaml` — `APP_DEBUG` should be `"false"` in production; leaving it `true` is what causes the misleading Blade-exception-page-crashes-itself failure mode described above |

### A note on environment variables not sticking (real, repeated failure mode on this project)

Render's dashboard for this service has, in practice, shown variables as present-but-masked when they were actually empty, silently dropped previously-working variables (`APP_KEY`/`APP_URL`/`DB_HOST`/`DB_USERNAME`/`DB_PASSWORD` have all disappeared from the service's variable list between one screenshot and the next during this project's actual deploy history), and the "link this Environment Group to the service" flow failed to persist a link multiple times without any visible error. **Do not trust the dashboard's rendered state as ground truth** when debugging a deploy. Instead, use the **Shell** tab on the Render service and run:
```
printenv | grep -E "APP_URL|DB_HOST|DB_USERNAME|DB_PASSWORD|APP_KEY"
```
This shows exactly what the running container's process environment actually contains. If a deploy is failing on a DB-connection error, this is the fastest way to confirm whether it's a real credential problem or a dashboard-didn't-save problem (which looks identical from the Logs tab alone).

A second, subtler version of the same failure: pasting a multi-line block of `KEY=value` pairs into a single Value field can concatenate two variables together — this project hit exactly that once, where `DB_HOST`'s value ended up literally containing the text `port=5432` (from an adjacent line), producing the very specific libpq error `could not translate host name "port=5432" to address`. If you ever see that exact error text, it means a value field contains stray text from a different variable, not that the hostname itself is wrong.

## Local development

`docker-compose.yml`:
- Bind-mounts the entire project into the container (`.:/var/www/html`) — meaning code changes are live without rebuilding, **except** `docker/entrypoint.sh` and `docker/nginx.conf`, which the `Dockerfile` copies to `/entrypoint.sh` and `/etc/nginx/sites-available/default` (outside the mounted path) — **editing either of those requires `docker compose build` + `docker compose up -d --force-recreate`, a plain restart is not enough**, since a restart re-runs the entrypoint script already baked into the current image, not your edited copy on disk.
- Only explicitly passes `DB_*`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` through its `environment:` block. Everything else (`APP_KEY`, `APP_URL`, `ADMIN_EMAIL`/`PASSWORD`, `BAKONG_*`) comes from your real local `.env` file, which — because of the bind mount — is read directly by Laravel's dotenv loader inside the container. **This means local dev has no equivalent of the "Render dashboard didn't save it" failure mode** — if it's wrong locally, it's wrong in your own `.env` file, full stop.
- Pins DNS to `1.1.1.1`/`8.8.8.8` — the default Docker DNS intermittently failed to resolve Neon's hostname, hanging ~30s before erroring.
- Points at the **same live Neon database** as production by default. Be deliberate before running destructive `artisan` commands (`migrate:fresh`, `db:wipe`, etc.) locally.

```bash
docker compose up -d --build     # first run / after any Dockerfile or docker/ change
docker compose logs app --tail=60
docker compose exec app php artisan tinker
docker compose exec app printenv | grep DB_       # ground truth for what the container sees
```

## Deploying

1. Push to `main` on GitHub.
2. On Render, go to `cec-electronic` and trigger **Manual Deploy** — this project's deploys have consistently shown up as "Manually deployed by you via Dashboard" rather than auto-triggering from the push, so don't assume a push alone starts a new deploy; check the **Deploys** tab for a new entry on the right commit.
3. Watch the **Logs** tab for the boot sequence above, ending in `App is ready at http://localhost:8080`.
4. If it fails, work through the troubleshooting checklist below rather than re-guessing at env var values from memory.

### Troubleshooting checklist

1. **Deploys tab** — did a new deploy actually start, on the commit you expect?
2. **Logs tab** — does the boot sequence complete? The DB-wait step's exact error message (not just "it failed") tells you whether it's a credentials problem, a stray-text-in-a-field problem (see above), or something else.
3. **Shell tab, `printenv`** — ground truth for what the container actually has, since the dashboard's variable list has been unreliable for this project specifically.
4. **`storage/logs/laravel.log`** — if you're seeing a 500 with a confusing/secondary-looking error on the debug page, check the log directly; the debug page itself can fail to render (see the view-cache section above) and mask the real exception.
5. **Local repro** — `docker compose up -d --build` reproduces the exact same image/entrypoint against the same Neon database, the fastest way to tell whether an issue is code (fixable by you, right now) or Render-environment-specific (dashboard/env var problem).
