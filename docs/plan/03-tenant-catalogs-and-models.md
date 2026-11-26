# Phase 3 — Tenant Settings, Materials, Media, and Valve Models

## Goal

Let Company Admins maintain the tenant's company identity, material catalog, and
valve-model master data, with private media and strict role/tenant isolation.

## Prerequisites

- Complete [Phase 2](02-super-admin-and-provisioning.md), including tenant migrations,
  seeded materials, and private directories.
- Confirm authenticated tenant middleware is applied to every page and AJAX endpoint.

## Work breakdown

### 1. Company settings

1. Add a tenant-scoped settings page for company name, address, GSTIN, header/footer
   text, and default notes.
2. Keep the settings record single-row (`id=1`); create/update it safely if needed.
3. Validate lengths and JSON structure and show field-level validation errors.
4. Add private logo upload with MIME/content validation, size limits, safe generated
   filenames, and preview in the browser.
5. Replace/delete previous logo files safely and avoid exposing private storage paths.

### 2. Materials catalog

1. Add a tenant-scoped, server-side DataTable with code, description, category,
   status, and actions.
2. Add Company Admin create/edit/deactivate workflows for material codes, names, and
   categories.
3. Validate unique codes within the tenant and prevent inactive materials from
   appearing in new selections.
4. Prevent deletion when a material is referenced by any BOM template; provide
   deactivation as the safe alternative.
5. Use the same tenant-scoped query and autocomplete source for later BOM and sales
   workflows.

### 3. Authenticated media proxy

1. Add an authenticated media route/controller for logos and valve schematics.
2. Resolve the storage root from the initialized tenant UID, not a request parameter.
3. Restrict access to expected media categories and safe relative paths; prevent
   path traversal and arbitrary file reads.
4. Set an appropriate content type and private/no-store caching policy for protected
   content.
5. Return explicit not-found/forbidden responses and log unexpected storage errors.

### 4. Valve models

1. Add tenant-scoped model create/edit/list screens with server-side DataTables.
2. Persist model name, valve category (function/role), valve type (mechanical
   design), design/testing/face-to-face/end-connection standards, optional
   attributes, active state, and private schematic path.
3. Use controlled dropdown choices for valve categories and types, validated on
   the server; keep both database columns as strings so the choice lists can evolve.
4. Validate and normalize custom key/value attributes on the server; do not trust
   the client-generated rows.
5. Add schematic upload and live preview using the private media route.
6. Enforce the Super Admin-configured active-model limit on creation/reactivation;
   a blank limit is unlimited.
7. Define deletion behavior so referenced models cannot break datasheets; prefer
   deactivation when historical records depend on the model.

### 5. Role and tenant enforcement

1. Restrict settings, materials, model, and team administration routes to
   `role=admin`.
2. Return `403` for authenticated sales users attempting these routes.
3. Apply tenant scoping to all reads, writes, validation queries, and media access.

## Acceptance criteria

- Company settings persist per tenant and never cross tenant boundaries.
- Admin can create/edit/deactivate materials and models; sales users receive `403`
  for administration routes.
- Active materials/models are presented to later workflows; inactive records are
  excluded from new selection.
- Referenced materials cannot be deleted; referenced models cannot be destructively
  removed in a way that breaks history.
- Uploads are validated and remain private; the media endpoint cannot access another
  tenant's files or arbitrary filesystem paths.
- Starter plan cannot exceed five active models, including reactivation cases.
- Tests cover role authorization, tenant isolation, material references, quota
  boundaries, and media access control.

## Exit deliverables

- Company settings UI and persistence.
- Materials catalog UI, validation, and server-side table endpoint.
- Authenticated tenant media controller.
- Valve model CRUD, private schematic upload, and plan-limit enforcement.

## Implementation status

The Phase 3 tenant catalog implementation is complete for the current MVP surface:

- Tenant databases now include a `valve_models` table in addition to company
  settings and materials.
- Company settings update through a tenant-scoped page and support private logo
  uploads, JSON default notes, and single-row `id=1` persistence.
- Materials provide tenant-scoped create/edit flows, allowlisted DataTables
  search/sort/filter inputs, and unique-in-tenant code validation.
- Valve models provide tenant-scoped create/edit/list flows, controlled function
  category and mechanical type dropdowns, private schematic uploads, custom JSON
  attributes, and per-tenant quota checks.
- Sales users receive `403` from settings, materials, and valve-model
  administration routes through `EnsureTenantAdmin`.
- Media requests are restricted to `logos` and `schematics`, remain under the
  initialized tenant UID directory, and return private/no-store responses.

The focused Phase 3 suite passes in SQLite, including tenant-admin role checks,
company settings persistence, material CRUD/uniqueness, dynamic model quotas,
private schematic storage, traversal rejection, and cross-tenant media isolation.

Existing tenant databases can be updated without losing model classifications:
the tenant migration renames `category` to `valve_type` and adds a blank
`valve_category`. Assign a function category to each existing model before using
it in a datasheet:

```sh
php artisan tenants:migrate --force --no-interaction
```

### QA checklist

- Confirm Company Admin can update all company settings, upload a logo, replace
  the logo, and verify repeated saves retain one settings row.
- Confirm Sales users receive `403` from settings, materials, and valve-model
  administration routes.
- Verify material code uniqueness, category/status filtering, sorting, pagination,
  and inactive material behavior.
- Verify the configured active-model limit blocks creation/reactivation and a blank
  limit remains unlimited.
- Upload invalid logo/schematic file types and oversized files; confirm validation
  fails without partial database or file writes.
- Verify uploaded media loads through the authenticated media route and cannot be
  accessed through another tenant UID or a traversal path.
- Confirm custom model attributes are normalized and cannot be used to inject
  arbitrary or nested server values.
- Edit a valve model without changing its name/type and confirm it saves.
  Confirm renaming to another existing name in the same type is rejected.
- Confirm the dropdowns contain the approved values:
  Valve Category: Manual Isolation, Automated On/Off, Control / Modulating,
  Check / Non-Return, and Safety / Pressure Relief; Valve Type: Ball, Globe,
  Gate, Butterfly, Plug, Needle, Diaphragm, and Check.
- Confirm Valve Category and Valve Type render as dropdowns and reject values
  outside their predefined choices.
- Run the tenant migration against existing model records and confirm their
  mechanical type values are preserved while datasheet selection waits for a
  function category.
- Verify tenant databases remain isolated and the Super Admin central routes never
  initialize tenant context.

Run the focused coverage with:

```sh
php artisan test --compact tests/Feature/TenantCatalogTest.php tests/Feature/TenantConfiguratorTest.php tests/Feature/TenantMediaTest.php
```
