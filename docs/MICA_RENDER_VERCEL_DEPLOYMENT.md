# Mica public demo deployment readiness

Prepared from c831c19. No resources, hosted database, deployment, push or merge were created.
This document describes the next deployment, not a deployed environment.

## CLOSED completion replay

The completion service previously threw an unhandled InvalidArgumentException for CLOSED
orders. It now throws SaleAlreadyCompleted while holding the existing order lock inside
the existing transaction. The API renders **409 Conflict**:

```json
{"message":"This sales order is already completed.","code":"SALE_ALREADY_COMPLETED","sales_order_id":9}
```

The response does not pretend a second request's allocation was applied. Existing permissions
and request validation still run. No payments, lot availability, allocations or inventory
movements change on replay. The POS CompletionSession already refetches after an error and
accepts CLOSED as completed, so no frontend/payment/pricing engine change is required.

## Backend: Render Docker runtime

PHP is not a native Render runtime: use Docker. The repository now includes Dockerfile,
.dockerignore and deploy/start.sh. Render builds from the backend repository root; do not
use Composer's `setup` or `dev` scripts, npm, `artisan serve`, or a watch command.
[Render Docker documentation](https://render.com/docs/docker).

Build command (Docker runtime uses the Dockerfile, not a native Build Command field):

```sh
docker build -t mica-backend .
```

The image's exact Composer build step is:

```sh
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts
php8 artisan package:discover --no-interaction
composer check-platform-reqs --no-dev
```

Start command (Docker CMD, or the equivalent Render Docker Command override):

```sh
sh /var/www/html/deploy/start.sh
```

The entrypoint creates writable directories, caches config/routes/views with runtime
environment values, fixes storage/bootstrap/cache ownership and execs Apache in the foreground.
It never runs migrations, seeds, reset, a queue worker or scheduler. Apache listens on PORT
(10000 default), serves **only /var/www/html/public**, honors Laravel rewrites, and denies
dotfiles. .env, vendor, caches, local uploads/logs and SQL dumps are excluded from build context.
The TLS certificate file is supplied by the host, never copied from development.

Runtime is PHP 8.3 Apache on Debian Bookworm. Composer requires PHP ^8.3; the Docker image
installs pdo_mysql, mbstring, bcmath, intl, gd, zip, opcache and gmp. Standard PHP core/XML,
DOM, ctype, curl, fileinfo, filter, hash, JSON, OpenSSL, session and tokenizer are needed by
Laravel/locked dependencies; Composer checks the installed image platform. GD supports
optional PDF images. No Redis, SQL Server driver, Node or Windows/XAMPP path is needed for POS.
The php8 executable is provided in the image. Worker count is capped at two, PHP memory at
128 MB per request and opcache at 64 MB; these are constraints, not a memory benchmark.

## Environment and database

Use **.env.demo.example** as the placeholder-only reference for Render environment settings.
Generate an independent APP_KEY; do not put actual secrets in Git or VITE variables.

Required settings:

```dotenv
APP_ENV=production
APP_DEBUG=false
DEMO_MODE=true
APP_KEY=<generated Laravel key>
APP_URL=https://<backend>.onrender.com
FRONTEND_URL=https://<frontend>.vercel.app
CORS_ALLOWED_ORIGINS=https://<frontend>.vercel.app
TRUSTED_PROXIES=*
DB_CONNECTION=mysql
DB_HOST=<managed hostname>
DB_PORT=<provider port>
DB_DATABASE=mica_demo
DEMO_DATABASE=mica_demo
DB_USERNAME=<user restricted to this database>
DB_PASSWORD=<secret>
DB_URL=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
MYSQL_ATTR_SSL_CA=/etc/secrets/managed-db-ca.pem
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=true
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=array
FILESYSTEM_DISK=local
LOG_CHANNEL=stderr
LOG_LEVEL=warning
SANCTUM_STATEFUL_DOMAINS=
DEMO_ANCHOR_DATE=<presentation date, YYYY-MM-DD>
```

Use a separately provisioned disposable database named mica_demo or mica_demo_<suffix>.
DEMO_DATABASE must exactly equal DB_DATABASE. The database user must have privileges only
on that database; provisioning is a host-admin action, not an application operation.
Request processing validates the physical read/write database and schema connection. A
wrong/failed connection is rejected with a generic 503 before operational controllers run.
The public demo's APP_ENV=production does **not** permit reset or demo seeding.

For managed hosting, supply the provider's CA certificate and require TLS server-side.
Do not use a numeric host that disagrees with its certificate or disable certificate validation.
Confirm a non-empty session Ssl_cipher with the chosen host before publishing. Actual remote
TLS/connectivity cannot be verified until a host is selected. SSL options use PHP 8.3-compatible
PDO constants. Do not use PostgreSQL or Render's native Postgres in place of MySQL.

Code inspection finds conventional Laravel MySQL/MariaDB schema builders, enum alterations,
JSON columns, InnoDB transactions/row locks, conditional foreign keys and utf8mb4_unicode_ci.
There are no required generated columns, MySQL-only 0900 collations or engine-specific JSON
query operators in the demo path. Short index names fit both engines' limits. Recommend a
currently supported MariaDB LTS host first because MariaDB 10.4.32 was actually exercised;
that local version is verification evidence, not a version recommendation. MySQL 8.x is an
expected compatible alternative, **not runtime-certified here**. Run the disposable readiness
tests against the chosen engine/version before deployment, with InnoDB and strict mode enabled.

## Initialization and explicit reset

Build-time installs dependencies. Startup only caches and serves. Database initialization
is a **separate, finite, operator-invoked** step against the dedicated database.
[Render deploy lifecycle](https://render.com/docs/deploys).

Use a secure initialization env file outside Git, with the same dedicated host, TLS CA,
database and DEMO_DATABASE settings. For this one-off process only set APP_ENV=demo and
DEMO_MODE=true. Keep the public service APP_ENV=production. Use the same verified Docker image:

```sh
docker run --rm --env-file /secure/mica-init.env -v /secure/managed-db-ca.pem:/etc/secrets/managed-db-ca.pem:ro mica-backend php8 artisan migrate --force --no-interaction
docker run --rm --env-file /secure/mica-init.env -v /secure/managed-db-ca.pem:/etc/secrets/managed-db-ca.pem:ro mica-backend php8 artisan db:seed --class=MicaDemoDatabaseSeeder --force --no-interaction
docker run --rm --env-file /secure/mica-init.env -v /secure/managed-db-ca.pem:/etc/secrets/managed-db-ca.pem:ro mica-backend php8 artisan migrate:status
```

The image has no baked config cache, so these isolated commands use their supplied env.
If using an existing running container's production cache, provide a distinct nonexistent
APP_CONFIG_CACHE path for the one-off process; do not rewrite the web container's cache.
Do not run migrations or seeds against the current development database.

Reset only after deliberately deciding to erase demo sessions/transactions:

```sh
docker run --rm --env-file /secure/mica-init.env -v /secure/managed-db-ca.pem:/etc/secrets/managed-db-ca.pem:ro mica-backend php8 artisan demo:reset --force --no-interaction
```

Existing reset protections remain: explicit boolean DEMO_MODE, APP_ENV local/testing/demo
only, production refusal, exact dedicated name/prefix, physical read/write identity and
schema-connection identity. External connection aliases are removed in demo mode. This
command is not an HTTP endpoint and is never part of Docker CMD or Render restart/deploy.

Free Render services have no shell or one-off jobs; initialize from a trusted local runner
or separately approved CI runner with narrowly scoped credentials. Paid pre-deploy migration
support is optional later. Do not automatically seed/reset in pre-deploy.
[Render free service limitations](https://render.com/docs/free).

## Demo isolation, auth and integrations

In demo mode only the configured primary mysql connection is retained (SQLite memory only
for tests). RMS/ERS/EMS/ers_dev aliases are absent. Their routes and RMS schedule remain
disabled. HTTP facade requests are blocked, only the array mail transport is configured,
queues run synchronously and remote PDF loading is disabled. SMTP/SMS/external storage are
not needed by POS; no external credentials should be supplied. APP_DEBUG is forced false
in demo mode. There is no browser command that grants access to another database.

The public Administrator can modify the disposable demo records: these credentials are
intentionally public, not a protection for sensitive data. Never seed real people, supplier
or employee data here. The database prefix guard cannot identify the business use of a database
named mica_demo: dedicated credentials and host isolation are mandatory. This is not a full
penetration test of unrelated legacy modules.

Auth uses Laravel Sanctum personal access tokens carried as Authorization: Bearer. The two
existing Axios clients use VITE_API_URL; the central interceptors attach the token. There is
no statefulApi middleware and no credential-cookie cross-origin requirement. Use exact HTTPS
origins, no wildcard CORS origin, allow Authorization/Content-Type and keep credentials false.
TRUSTED_PROXIES=* is intended only behind Render's ingress; only forwarded for/port/proto are
trusted, not forwarded host. Database-backed sessions/cache survive service restart; keep
APP_KEY stable. Frontend password-reset URL now comes from cached config rather than env().

Demo-only credentials (all normally Laravel-hashed):

| Account | Email | Password |
| --- | --- | --- |
| Administrator | admin@mica.demo | MicaDemo2026! |
| Manager | manager@mica.demo | MicaDemo2026! |
| Cashier | cashier@mica.demo | MicaDemo2026! |

## Health and frontend: Vercel

Reuse **GET /up**, Laravel's existing lightweight liveness endpoint. It requires neither
authentication nor a database query and exposes no configuration. Set Render's Health Check
Path to /up. Liveness does not certify schema/data readiness; use authenticated POS checks
after initialization. Do not add database credentials or filesystem paths to health output.

Frontend is React/TypeScript/Vite, not Next.js. Vercel settings:

- Framework preset: Vite; repository root: frontend repository.
- Install: npm ci; build: npm run build; output: dist; Node: 22 LTS, at least 22.12 for Vite 8.
- Public build variable: VITE_API_URL=https://<backend>.onrender.com/api (include /api).
- Set the variable separately for each intended deployment environment, then rebuild.
- Do not put APP_KEY, DB_PASSWORD, access tokens or other secrets into VITE variables.

Frontend currently has no vercel.json. Before deploying it, add this routing configuration
to that repository as part of the separately authorized frontend deployment preparation:

```json
{"rewrites":[{"source":"/(.*)","destination":"/index.html"}]}
```

It enables direct /pos and /sales/sales-orders/9 navigation. The backend preparation commit
does not silently commit changes in the separate frontend repository. Vercel/API browser
behavior must be smoke-tested with actual chosen origins after deployment.
[Vercel Vite documentation](https://vercel.com/docs/frameworks/frontend/vite).

## Concrete free-instance constraints and verification limits

Cold starts follow idle suspension; local files/uploads/cache files disappear on restart.
Persist business data, sessions and application cache in the external database; stderr logs
go to the platform. POS receipt printing is client-side and needs no persisted server PDF.
Employee attachments have schema but no implemented upload/storage writer in the inspected
service path. Do not promise upload persistence until object storage or a paid disk is deliberately
configured. S3 also requires a driver package/configuration, not just FILESYSTEM_DISK=s3.
No worker/scheduler is required for Mica. Free instance external database traffic and platform
quotas can suspend service; this environment is for demos, not reliable paid-client operations.
Apache/PHP limits constrain parallel requests, so concurrent traffic still needs measurement.

Verified: backend suite (204 tests, 1,232 assertions) and focused safety tests (6 tests,
18 assertions); MariaDB readiness tests (6 tests, 182 assertions); fresh disposable MariaDB install/seed/reset;
three demo accounts/permissions; retail/wholesale completion and 409 replay with unchanged
payments/movements/stock; inventory consistency; /up; exact-origin CORS; frontend lint/build
with a placeholder HTTPS API URL; all 32 POS frontend tests. Production-style cached config
and HTTPS/bearer runtime are verified separately against the disposable database. Route
and view caching also pass. Syntax checks cover all 13 changed PHP files; targeted Pint and
git diff --check pass.

**Docker daemon is unavailable locally.** Linux image build, Apache startup and composer
--no-dev image platform verification remain mandatory checks before actual deployment.
MySQL 8 runtime, actual managed TLS, Render ingress and Vercel rewrites are not verified here.
There are no new migrations or business-rule changes in this checkpoint.
