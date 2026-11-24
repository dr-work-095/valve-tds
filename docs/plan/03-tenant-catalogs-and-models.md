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
2. Persist model name, category, design/testing/face-to-face/end-connection standards,
   optional attributes, active state, and private schematic path.
3. Use HTML autocomplete suggestions for repeated categories and engineering
   standards while allowing legitimate custom values.
4. Validate and normalize custom key/value attributes on the server; do not trust
   the client-generated rows.
5. Add schematic upload and live preview using the private media route.
6. Enforce the starter plan's maximum of five active models on creation/reactivation.
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
