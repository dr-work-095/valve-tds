# Phase 2 — Super Admin Portal and Tenant Provisioning

## Goal

Provide the minimum operational portal for manual tenant/subscription administration
and reliable asynchronous creation of each tenant's isolated database.

## Prerequisites

- Complete [Phase 1](01-core-setup-and-tenant-auth.md).
- Confirm the central schema and canonical tenant/database naming convention.
- Configure and verify the queue connection used for provisioning; provisioning must
  report failure rather than leave an apparently active but unusable tenant.

## Work breakdown

### 1. Admin dashboard and tenant listing

1. Add `/admin` dashboard metrics:
   - Total tenants.
   - Active tenants.
   - Tenants expiring within seven days.
2. Add an admin-only tenants listing with server-side DataTables:
   - Company name, plan, status, subscription end, and actions.
   - Global search, relevant filters, page lengths 10/25/50/100, and horizontal
     scrolling.
3. Scope every query to the central connection and validate DataTables ordering and
   filtering inputs against an allowlist.

### 2. Tenant creation workflow

1. Add a validated create form for company, plan, status, subscription end, notes,
   and initial Company Admin credentials.
2. Generate `tenant_uid` from the company name as
   `tenant_<lowercase-alphanumeric-company-name>`, add a numeric suffix when
   duplicate UIDs exist, and validate the result before using it as a tenancy or
   database identifier. Keep the numeric `tenants.id` as the primary key; never use
   raw user-provided strings as SQL identifiers.
3. Hash the temporary password and create the central tenant and initial
   `role=admin` user in one central transaction.
4. Dispatch `ProvisionTenantDatabase` only after that transaction commits.
5. Represent provisioning progress/failure explicitly in the admin UI and logs.
   Provisioning failure must not be presented as success.
6. Make retries safe: rerunning a job must not duplicate seed data or corrupt an
   existing tenant database.

### 3. Tenant provisioning job

1. Create the tenant database using the selected canonical naming convention.
2. Initialize the tenant connection using package-supported tenancy APIs.
3. Run tenant migrations and seed the required single `company_settings` row.
4. Seed the agreed 25–30 standard material grades, with deterministic/unique codes
   and an idempotent seeder.
5. Create private tenant directories for logos, schematics, and datasheets.
6. Record provisioning completion and the database name in tenancy metadata.
7. On errors, persist a failed state, capture useful logs, and define cleanup/repair
   behavior that avoids deleting a database containing valid tenant data.

### 4. Tenant edit, renew, suspend, and plan limits

1. Add an edit/renew workflow to update plan, subscription end, active/suspended
   status, and append offline payment references to admin notes.
2. Preserve useful audit history for subscription changes using the repository's
   existing conventions; at minimum retain notes and timestamps.
3. Store usage limits as per-tenant tenant-data values managed by Super Admin:
   - active users, active valve models, and monthly generated datasheets.
   - A blank limit means unlimited; plan names no longer determine limits by hardcoded
     code.
   - Enforce the effective per-tenant limit at the operation that consumes the resource.
4. Return a clear limit-reached response and ensure failed operations do not partially
   create records.

## Acceptance criteria

- Only active Super Admin accounts can access admin routes.
- Admin routes remain on the central connection.
- Dashboard counts reflect central tenant records and the seven-day window correctly.
- Server-side tenant listing supports safe search, filters, and pagination.
- Tenant creation records the tenant and initial admin, then provisions its isolated
  database and directories.
- Provisioning is idempotent or safely retryable, and its failure is visible.
- Renew/suspend operations update tenant state consumed by Phase 1 gating.
- Usage limits are enforced server-side and are covered at limit and over-limit
  boundaries.
- Tests cover central transaction behavior, provisioning success/failure, idempotent
  seed behavior, and plan limits.

## Exit deliverables

- Minimal Super Admin dashboard and tenant DataTable.
- Tenant create/edit/renew/suspend workflows.
- Provisioning job, tenant migrations/seeders, and private storage layout.
- Automated tests for lifecycle and quota boundaries.

## Implementation status

The Phase 2 admin portal and provisioning workflow are implemented so far:

- Central `tenants.id` is an auto-increment integer primary key. `tenant_uid` is
  the unique tenant/business key stored as `tenant_{companyname}` with lowercase
  letters and numbers only. It is used for tenant database names and tenancy
  initialization; user memberships reference `tenant_uid`.
- Existing databases should run `php artisan migrate` to add/backfill `tenant_uid`.
  The migration converts MySQL tenant primary keys to auto-increment integers and
  updates existing user memberships. Back up the central database before running
  it on production data.

- The admin dashboard links to a central-connection tenant list with server-side
  search, plan/status filters, pagination, and allowlisted sorting.
- Active Super Admin middleware protects dashboard and tenant-management routes.
- Tenant creation validates company/subscription details and initial Company Admin
  credentials, then persists both central records in one transaction before it
  dispatches provisioning.
- Tenant editing updates subscription state and appends timestamped payment notes.
  Failed database provisioning can be retried from the tenant page.
- Provisioning creates the tenant database, runs tenant migrations, idempotently
  seeds one company-settings row and 30 standard materials, creates private
  directories, and records ready/failed status with logs.
- Plan-limit definitions and boundary tests cover active users, models, and monthly
  PDFs. The current app has no later-phase user/model/PDF creation endpoints, so
  those quotas still need to be enforced at the corresponding write operations
  when those features are implemented.

The SQLite feature suite passes. Phase 2 is ready for manual QA, but production
database provisioning and the quota-consuming workflows remain to be verified or
completed as noted below.

Run the local development processes with `composer dev`; the queue listener handles
both `provisioning` and `default` queues. If running a worker separately, use:

```sh
php artisan queue:work database --queue=provisioning,default --tries=3 --timeout=270
```

The central database must be created and migrated before using the portal. The
provisioning worker also requires the central queue and cache tables, as well as a
database account allowed to create tenant databases. Automated feature tests use
SQLite; provisioning against the configured local MySQL server still needs QA
verification.

### QA checklist

- Confirm inactive admins are redirected to the Super Admin login and active admins
  can list, create, edit, and retry tenant provisioning.
- Create tenants on each supported plan; verify a canonical `tenant_uid` and database
  name, the central Company Admin membership, and a hashed temporary password.
- Verify required-field and duplicate-email validation does not leave partial
  central records.
- Exercise DataTable search, plan/status filters, all page lengths, sorting, and
  malformed ordering input.
- Verify provisioning creates a separate database, one settings row, 30 materials,
  and private `logos`, `schematics`, and `datasheets` directories. Run provisioning
  again and confirm it does not duplicate seed data.
- Force a provisioning failure, confirm the failed state and operator-visible
  message, then retry after correcting the cause.
- Renew and suspend tenants; confirm subscription end dates, timestamped payment
  notes, and Phase 1 tenant-access gating behave as expected.
- Set tenant limits explicitly in Super Admin and verify active-user, active-model,
  and monthly-PDF boundaries. Confirm zero/blank values behave as configured
  (zero blocks creation; blank means unlimited).
- Confirm each tenant database and private file directory is isolated from other
  tenants.

The focused automated coverage is run with:

```sh
php artisan test --compact tests/Feature/TenantManagementTest.php tests/Feature/TenantProvisioningTest.php
```
