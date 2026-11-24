# Phase 1 — Core Setup, Dual Guards, and Session-Bound Tenancy

## Goal

Establish the central identity model and request lifecycle that safely separates
Super Admin operations from authenticated tenant users. Tenant resolution must be
session/user-bound; tenant UIDs must never be accepted from arbitrary request input.

## Prerequisites

- Read the source schema and tenancy requirements in
  [`../valve_tds_generator_detailed_mvp_implementation_plan.md`](../valve_tds_generator_detailed_mvp_implementation_plan.md).
- Confirm the actual Laravel/PHP versions and compatibility of Laravel 13 and
  `stancl/tenancy` v3 with the current project before altering dependencies.
- Decide the canonical tenant UID and database-name format. Keep it consistent with
  tenancy metadata and the database privileges intended for tenant provisioning.

## Work breakdown

### 1. Establish central persistence

1. Configure the central MySQL connection and document the expected connection name.
2. Add central migrations for:
   - `admins`: name, unique email, hashed password, active flag, remember token,
     timestamps.
   - `tenants`: auto-increment integer ID, unique tenant UID, company name, plan,
     subscription status, optional end date, admin notes, tenancy metadata, timestamps.
   - `users`: tenant UID foreign key, name, unique email, hashed password, role, active
     flag, timestamps.
3. Add model relationships and casts for dates, booleans, and JSON metadata.
4. Ensure the tenant-user email uniqueness rule matches the source requirement
   (central uniqueness, not per-tenant database uniqueness).
5. Add a safe initial-admin creation procedure suitable for deployment; do not commit
   real credentials.

### 2. Configure independent guards

1. Configure an `admin` guard/provider backed by the central `admins` table.
2. Configure the `web` guard/provider backed by central `users`.
3. Verify both providers use the central connection regardless of whether a tenant
   connection has previously been initialized in the request lifecycle.
4. Add route groups for `/admin/*` and tenant application routes, applying the
   corresponding guard middleware explicitly.
5. Add login/logout flows and prevent inactive admins or users from authenticating.

### 3. Add tenant initialization middleware

1. Register `InitializeUserTenant` after successful web authentication.
2. Resolve `tenant_uid` from the authenticated central user's `tenant_id` membership
   value only.
3. Reject missing tenant memberships, inactive users, missing tenant records, and unavailable
   tenant databases with an explicit user-facing failure and operational logging.
4. Before initializing tenancy, check the central tenant subscription state:
   - `suspended` and `expired` tenants are blocked.
   - An `active` or `trial` tenant whose subscription end is in the past is marked
     `expired` centrally and blocked.
   - Define and test the policy for a null subscription end date; do not infer an
     expiry from a missing value.
5. Initialize the database-per-tenant context only after the central gate passes.
6. Ensure tenant context is ended/reset after the request, including failure paths,
   using the tenancy package's supported lifecycle mechanism.
7. Add a dedicated inactive-subscription screen/response and login redirect behavior
   for missing or inactive user membership.

### 4. Enforce route and connection boundaries

1. Ensure `/admin/*` routes never invoke tenant initialization.
2. Ensure tenant routes and their AJAX/DataTables endpoints cannot bypass the
   authentication/tenant middleware.
3. Ensure logout and guard switching do not reuse a stale tenant database connection.
4. Add central-connection assertions to admin operations and tenant-connection
   assertions to a representative authenticated tenant request.

## Acceptance criteria

- Admin authentication reads/writes only the central database and never initializes
  a tenant connection.
- A web user can access only the database associated with that user's central
  `tenant_id`, where that membership value stores the tenant's `tenant_uid`.
- Missing, inactive, suspended, expired, and overdue memberships are blocked with
  an explicit response; overdue subscriptions are updated to `expired`.
- A request cannot choose a tenant by changing URL, query, form, or header values.
- Tenant context is reset between requests and does not leak across guard changes.
- Feature tests cover guard isolation, tenant resolution, subscription gating,
  inactive accounts, and tenant context cleanup.
- Migrations can be applied on a clean central database and roll back safely.

## Exit deliverables

- Central migrations and models for admins, tenants, and users.
- Dual guard/provider configuration and authentication routes.
- Tenant initialization and subscription-gating middleware.
- Tests demonstrating guard and tenant isolation.
- Documented tenant UID/database naming convention.

## Implementation status

### Completed core setup

- The central Laravel connection is named `central`. Tests run against SQLite.
  Configure `CENTRAL_DB_*` in `.env` for a separate central database; these
  settings can also inherit the `DB_*` values. MySQL deployments must create the
  configured central database before running migrations.
- Central migrations and models cover `admins`, `tenants`, and `users`, including
  the unique central tenant-user email, tenant foreign key, relationships, and
  casts.
- Central `tenants.id` is an auto-increment integer primary key.
  `tenants.tenant_uid` is the unique tenancy key and uses
  `tenant_<lowercase-alphanumeric-company-name>` (for example,
  `tenant_northplant`). Duplicate company names receive a numeric suffix.
  With the tenancy database prefix `tds_`, tenant database names use
  `tds_<tenant_uid>`, for example `tds_tenant_northplant`.
- Central `users.tenant_id` is a foreign key to `tenants.tenant_uid`, not
  `tenants.id`.
- Separate `admin` and `web` guards, providers, login/logout routes, and
  active-account authentication checks are implemented. The Super Admin login
  ignores and clears stale tenant login redirect URLs.
- `InitializeUserTenant` resolves the tenant only from the authenticated central
  user's `tenant_id` and resolves `tenants.tenant_uid`. It blocks missing memberships and missing tenant records,
  checks subscription status before initialization, logs and responds when the
  tenant database is unavailable, and ends tenancy after each request.
- Suspended and expired tenants are blocked. Overdue active/trial subscriptions
  are marked expired. A null subscription end date does not cause expiry.
- `php artisan admin:create` interactively creates the initial Super Admin
  without committing credentials or seeding a default account.
- Super Admin and tenant starter dashboards use the responsive sidebar and
  summary-card styling from `docs/sample.html`.
- Automated core coverage is in `tests/Feature/CoreSetupTest.php`. It verifies the
  numeric tenant primary key, company-based `tenant_uid` memberships, guard
  isolation, subscription gating, context cleanup, and rejection of numeric
  tenant IDs used as membership context.

### Still to complete in Phase 1

- Super Admin tenant management UI and workflows: tenant list, create, edit,
  renew, suspend, and subscription notes.
- Tenant database provisioning, tenant migrations, initial tenant data/catalog
  seeding, and a provisioning job or equivalent workflow.
- Automated scheduled expiry processing.
- Full acceptance testing against MySQL, including real tenant database
  provisioning and tenant-connection behavior. Automated tests currently use
  SQLite and do not provision a production-style MySQL tenant database.

## QA checklist

Run the automated core tests first:

```sh
php artisan test --compact tests/Feature/CoreSetupTest.php
```

For manual QA, use a disposable database/environment. Do not run migration
rollback or tenant provisioning checks against production data.

### Database and setup

- [ ] Create an empty central database matching `CENTRAL_DB_DATABASE`, configure
  the central connection in `.env`, and run `php artisan migrate --database=central`.
- [ ] Verify `admins`, `tenants`, and `users` exist on the central connection,
  and that a second migration run is safe.
- [ ] Verify `tenants.id` is an auto-increment primary key and
  `tenants.tenant_uid` is unique; verify `users.tenant_id` is a foreign key to
  `tenants.tenant_uid`, not `tenants.id`.
- [ ] Verify rollback on a disposable database, then migrate it again.
- [ ] On an existing database, back up first and verify the tenant UID migration
  adds `tenants.tenant_uid`, backfills company-based UIDs, updates
  `users.tenant_id`, and converts `tenants.id` to an auto-increment primary key.
- [ ] Confirm tenant database create privileges and verify generated tenant
  database names follow `tds_tenant_<lowercase-alphanumeric-company-name>`.
- [ ] Run `php artisan admin:create`; verify valid input creates an admin,
  duplicate email and mismatched/short passwords are rejected, and no default
  credentials exist.

### Authentication and guard isolation

- [ ] Sign in at `/admin/login` with valid credentials and confirm the admin
  dashboard opens at `/admin/dashboard`, including when the session contains a
  stale intended URL such as `/dashboard`.
- [ ] Confirm invalid credentials and inactive admins cannot sign in.
- [ ] Confirm admin dashboard and logout requests do not initialize or reuse a
  tenant database connection.
- [ ] Sign in at `/login` with valid tenant-user credentials; confirm invalid
  credentials and inactive users are rejected.
- [ ] Confirm admin and tenant guards do not authenticate each other, including
  when the same email exists in both central tables.
- [ ] Confirm sign-out invalidates the appropriate guard/session and does not
  leave a tenant connection active.

### Tenant resolution and subscription gate

- [ ] With two tenants and users, confirm each authenticated user can only reach
  the database associated with their central `tenant_id`/`tenant_uid`.
- [ ] Change tenant IDs or UIDs in URL paths, query strings, form fields, and headers;
  verify the request cannot switch tenant context.
- [ ] Attempt to use a numeric `tenants.id` value as `users.tenant_id`; confirm it
  does not resolve a tenant context and is rejected by the membership check.
- [ ] Confirm missing tenant membership and missing central tenant records are
  rejected with the expected login/error response.
- [ ] Confirm active and trial tenants with null or future subscription end dates
  can access their workspace.
- [ ] Confirm suspended and already-expired tenants are blocked with the
  subscription-inactive response.
- [ ] Confirm an overdue active/trial subscription is marked `expired` centrally
  and blocked on that request.
- [ ] Remove or make a tenant database unavailable; confirm the user sees the
  service-unavailable screen and the failure is operationally logged.
- [ ] After successful, blocked, and failed tenant requests, confirm tenancy is
  ended and the central connection is restored before the next request.

### Dashboard and responsive behavior

- [ ] Verify both dashboards render the signed-in user's name and the expected
  role/company details without exposing data from another tenant.
- [ ] Check the sample-inspired sidebar, cards, and tables at desktop and mobile
  widths; verify mobile navigation opens and closes and sign-out remains usable.
