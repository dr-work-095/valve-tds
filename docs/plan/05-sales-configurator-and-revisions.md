# Phase 5 — Sales Configurator, Immutable Snapshots, and Revisions

## Goal

Allow Sales users to configure a datasheet from ready valve models, freeze the
resolved engineering/company data in an immutable snapshot, and create locked
revision history.

## Prerequisites

- Complete [Phase 4](04-dimensions-and-bom.md).
- Confirm tenant tables exist for `generated_datasheets` and that the required foreign
  keys/indexes are in place.
- Define a concurrency-safe document-number allocation strategy and revision-number
  transaction/locking strategy before implementation.

## Work breakdown

### 1. Datasheet registry and listing

1. Add tenant-scoped `generated_datasheets` persistence with queued/generated/failed
   states, private PDF path, creator, parent revision, and JSON snapshot.
2. Add a server-side DataTable showing document number, revision, client, model,
   selected size/class, creator, status, creation date, and actions.
3. Include safe search and filters; scope every query to the authenticated tenant.
4. Show status-specific actions: queued is generating, generated can be downloaded,
   and failed can be retried after Phase 6 provides the retry workflow.

### 2. Configurator UI and lookup endpoints

1. Keep the first create step compact: collect client name, project reference,
   document number, valve model, and dimension. After Next, show the engineering
   datasheet layout with compact editable inputs for the selected catalog values,
   BOM, and project-specific identification, operating, performance, actuation,
   compliance, notes, and revision sign-off data.
2. Provide suggestions for returning client names and searchable model/material
   selection using the source plan's autocomplete patterns.
3. When a ready model is selected, return its available dimension combinations and
   BOM using authenticated tenant-scoped endpoints.
4. When a dimension tuple is selected, display the matching recorded measurements.
5. Show default notes and allow sales users to edit notes for this document.
6. Validate all endpoint IDs and fields on the server; ignore or reject forged
   dimensions/BOM values not belonging to the selected model and tenant.
7. Keep project-specific line size, operating conditions, sizing, actuation,
   compliance, and sign-off on the datasheet. Prefill reusable mechanical
   construction, flow characteristic, and default seat-leakage class from Valve
   Model; prefill selected size values from Valve Dimensions and BOM values from
   Valve BOM. Allow edits to these prefills in the datasheet snapshot without
   modifying the reusable catalog records.

### 3. Quota and snapshot creation

1. Count datasheets created in the current calendar month and apply the tenant's
   Super Admin-configured monthly datasheet limit; reject creation when that limit
   is reached. A blank limit means unlimited.
2. Perform quota checking and creation with an approach that avoids concurrent
   submissions exceeding the limit.
3. Resolve selected material IDs server-side into stable display strings (code and
   name); store values, not just live catalog references. Snapshot BOM component
   group, part name, quantity, unit, and notes with each material.
4. Freeze company settings, model metadata, selected dimension values, ordered BOM
   component details/materials, notes, client/project data, document number, and
   revision data in `selected_specs`.
5. Store project-specific values in the same immutable JSON snapshot; do not add
   per-field database columns. Control performance fields apply to modulating models.
6. Validate snapshot shape before persistence and use a database transaction for the
   datasheet record and related state changes.
7. Ensure future edits/deactivation of source records do not modify an existing
   snapshot.

### 4. Revision workflow

1. Add a create-revision action that clones the source datasheet snapshot as the basis
   for a new revision.
2. Link the new row to the prior revision/original using `parent_datasheet_id`.
3. Determine the next revision number for the same document under a transaction or
   lock so simultaneous requests cannot allocate duplicates.
4. Store revisions as immutable records; do not overwrite previous revisions or their
   PDFs.
5. Define and test how revision creation handles a failed/queued original and whether
   the user can edit the cloned configuration before submission; preserve existing
   source behavior unless a product decision changes it.

## Acceptance criteria

- Sales users can create records only for ready models and valid tenant-owned
  dimensions/materials.
- The dynamic tenant quota blocks creation at the configured monthly limit and is
  tested at boundary and concurrent conditions.
- Snapshot contains all display-critical company/model/dimension/BOM/note values and
  remains unchanged after source catalog edits.
- Revisions increment monotonically, link to their parent, and do not alter any prior
  revision or snapshot.
- Document number/revision uniqueness is enforced by database constraints and
  conflict-safe application logic.
- Feature tests cover valid and invalid configurator payloads, tenant boundaries,
  snapshot immutability, quota, and revision concurrency.

## Exit deliverables

- Datasheet listing and configurator UI/endpoints.
- Immutable snapshot creation and monthly quota enforcement.
- Conflict-safe revision creation and history display.

## Implementation status

The Phase 5 datasheet implementation is complete for the current MVP surface:

- Added tenant-scoped `generated_datasheets` persistence with unique document/revision
  constraints, immutable JSON snapshots, private PDF fields, and parent revision links.
- Added Sales-accessible datasheet listing, configurator, tenant-scoped lookup
  endpoints, and ready-model filtering.
- Datasheet creation uses a compact setup step followed by an editable,
  PDF-template-style datasheet editor. The Next action only prepares the editor;
  final submission creates the immutable snapshot and queues PDF generation.
- Prefilled model, dimension, and BOM values can be edited per datasheet and are
  saved only in its snapshot, leaving reusable catalog records unchanged.
- The nominal-size cell uses one combined text override for body and line sizing;
  selected catalog dimension values remain preserved separately in the snapshot.
- Datasheet snapshots freeze company data, model metadata and reusable construction
  defaults, selected dimensions, ordered BOM component details and resolved material
  display values, notes, client/project data, and optional engineering conditions
  captured from the datasheet form.
- The PDF follows the two-page industrial datasheet template with identification,
  operating conditions, mechanical construction, BOM, control performance,
  actuation, testing/compliance, and revision sign-off sections.
- Cross-model dimensions and forged/inactive/ineligible BOM material selections are
  rejected server-side.
- Revisions clone the source snapshot, allocate monotonic revision numbers, and link
  to the original datasheet without overwriting prior records.
- Super Admin controls dynamic per-tenant limits for active users, active valve
  models, and monthly datasheets. Blank limit values mean unlimited.
- Creation and revision quota checks use tenant-specific values and database
  transactions/locks for the configured boundaries.

The focused configurator suite passes for dynamic quota boundaries, snapshot
immutability, cross-model validation, and revision linkage.

### QA checklist

- Confirm Super Admin can set or clear active-user, active-model, and monthly
  datasheet limits for each tenant.
- Create datasheets at the configured monthly limit, then confirm the next creation
  is rejected and concurrent submissions cannot exceed the limit.
- Confirm changing a tenant limit takes effect without code deployment or restart.
- Select a ready model and verify its dimensions and BOM values prefill the editor.
- Confirm the first step contains only client/project/document and valve/dimension
  selection, and that Next creates neither a datasheet record nor a PDF job.
- Confirm the second step presents compact editable fields in the datasheet layout,
  prefilled values can be changed, Back preserves the setup values, and edits affect
  only the new datasheet snapshot rather than catalog records.
- Confirm datasheets source model construction and BOM component details from the
  catalog, while line size and operating/actuation values remain project-specific.
- Enter optional values across the engineering datasheet sections; confirm values
  are validated, frozen in the snapshot, and rendered in the generated PDF.
- Confirm control-performance inputs and PDF section are only shown for
  Control / Modulating models; leave non-applicable fields blank.
- Submit a forged dimension or material from another model/tenant and confirm
  validation rejects it.
- Edit source company, model, dimension, or material records after creation and
  confirm the existing snapshot remains unchanged.
- Confirm snapshots preserve model construction defaults and BOM component group,
  quantity, unit, notes, and resolved material labels.
- Create revisions only from generated datasheets and confirm each revision number
  is monotonic and linked to the original.
- Confirm document number/revision uniqueness is enforced by the database.
- Run focused coverage with:

```sh
php artisan test --compact tests/Feature/TenantConfiguratorTest.php tests/Feature/TenantManagementTest.php
```
