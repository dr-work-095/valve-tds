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

1. Add a create form for client name, project reference, document number, model,
   dimension combination, BOM materials, and project notes.
2. Provide suggestions for returning client names and searchable model/material
   selection using the source plan's autocomplete patterns.
3. When a ready model is selected, return its available dimension combinations and
   BOM using authenticated tenant-scoped endpoints.
4. When a dimension tuple is selected, display the matching recorded measurements.
5. Show default notes and allow sales users to edit notes for this document.
6. Validate all endpoint IDs and fields on the server; ignore or reject forged
   dimensions/BOM values not belonging to the selected model and tenant.

### 3. Quota and snapshot creation

1. For starter tenants, count datasheets created in the current calendar month and
   reject new creation at 100 or more.
2. Perform quota checking and creation with an approach that avoids concurrent
   submissions exceeding the limit.
3. Resolve selected material IDs server-side into stable display strings (code and
   name); store values, not just live catalog references.
4. Freeze company settings, model metadata, selected dimension values, ordered BOM
   parts/materials, notes, client/project data, document number, and revision data in
   `selected_specs`.
5. Validate snapshot shape before persistence and use a database transaction for the
   datasheet record and related state changes.
6. Ensure future edits/deactivation of source records do not modify an existing
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
- Starter quota blocks creation at the stated monthly limit and is tested at 99, 100,
  and concurrent boundary conditions.
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
