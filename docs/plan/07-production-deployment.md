# Phase 7 — Production VPS Deployment and Operations

## Goal

Deploy the completed MVP to an Ubuntu LTS VPS with production dependencies, tenant
database provisioning privileges, reliable queue/scheduler processes, HTTPS, and
recoverable backups.

## Prerequisites

- Complete Phases [1–6](README.md#plan-sequence) and pass their tests.
- Confirm production environment values, domain, DNS, administrator bootstrap
  procedure, backup destination/retention, and recovery owner.
- Never place secrets in source control or deployment documentation.

## Work breakdown

### 1. Server and runtime

1. Provision Ubuntu 22.04 or 24.04 LTS at the source-plan baseline of 4 vCPU and 8 GB
   RAM or greater.
2. Install and patch Nginx, PHP-FPM 8.3/8.4 with required extensions, MySQL 8.0+,
   Redis 7.x, and Node.js LTS/npm.
3. Configure a non-root application user and firewall policy; expose only required
   public ports.
4. Configure TLS with Certbot and verify HTTP redirects to HTTPS.
5. Install Chromium/Chrome and required headless dependencies/fonts; verify the
   Browsershot runtime as the same user/account that will run queue workers.

### 2. MySQL and tenant provisioning privileges

1. Create the central database with `utf8mb4`, InnoDB, and strict SQL mode.
2. Create a dedicated least-privilege application account.
3. Grant central database access and only the tenant database privileges required
   for provisioning/migration; validate the wildcard database grant syntax against
   the selected MySQL version.
4. Verify the app can create, migrate, and connect to a test tenant while being
   unable to access unrelated schemas.
5. Restrict administrative/root credentials to provisioning operations; do not put
   them in `.env` unless required and explicitly accepted by the security design.

### 3. Application release

1. Deploy a tagged/reviewed application release to `/var/www/valvetds`.
2. Create production `.env` securely with `APP_ENV=production`, `APP_DEBUG=false`,
   central DB connection, Redis queue/session configuration, and application key.
3. Install optimized PHP dependencies and build Vite assets in the release process.
4. Run central migrations with `--force` and perform the approved Super Admin
   bootstrap without hard-coded credentials.
5. Set ownership and minimum required permissions for `storage/` and
   `bootstrap/cache/`; keep tenant media outside the public web root.
6. Configure Nginx/PHP-FPM to serve the correct public directory and apply security
   headers and request-size limits appropriate to the app.

### 4. Workers, scheduler, and backups

1. Configure Supervisor for 2–4 worker processes listening to `high,low`, with sleep
   3 seconds, 3 tries, and 60-second timeout as specified.
2. Ensure workers restart on release and are monitored for failures/restarts.
3. Add the Laravel Scheduler cron entry under the application service account.
4. Configure encrypted/off-site daily backups for the central database, every tenant
   database, and `storage/app/tenants/`.
5. Define backup retention, alerting, and a documented restore procedure. Periodically
   test restoration to an isolated environment; a successful backup command alone
   is not sufficient evidence of recoverability.

### 5. Release verification and operations

1. Run migrations and verify admin login, tenant login, tenant provisioning, and
   subscription gating in production-like configuration.
2. Render and download a test PDF; verify private files cannot be fetched publicly.
3. Verify high/low queue operation, failed-job visibility, scheduler execution, and
   log rotation/permissions.
4. Confirm health checks/monitoring cover web availability, queue depth, failed jobs,
   storage capacity, database health, and backup completion.
5. Document rollback procedure that preserves tenant data and prevents old code from
   interpreting newer schema incorrectly.

## Acceptance criteria

- HTTPS production release is reachable with debug disabled and no committed/runtime
  secret exposure.
- Super Admin and tenant login work; tenant provisioning can create a schema using
  only the configured application privileges.
- Vite assets are built and served; PDF rendering works under the worker service
  account.
- Supervisor prioritizes `high` before `low`, and the scheduler runs once per minute
  to invoke scheduled tasks.
- Daily backups include central and tenant databases plus tenant files, are stored
  off-site, and a restore drill succeeds.
- Logs, alerts, storage permissions, and rollback steps are operationally documented.

## Exit deliverables

- Production VPS with HTTPS and least-privilege database/application accounts.
- Application, worker, and scheduler service configuration.
- Verified PDF smoke test, backup job, and successful restore drill.
- Deployment, rollback, and operational runbook.
