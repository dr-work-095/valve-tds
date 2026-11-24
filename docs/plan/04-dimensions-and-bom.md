# Phase 4 — Dimension Matrix and BOM Templates

## Goal

Complete each valve model's engineering configuration with unique dimension
combinations and an ordered, validated BOM that can be consumed by the configurator.

## Prerequisites

- Complete [Phase 3](03-tenant-catalogs-and-models.md), including models and materials.
- Confirm tenant migrations contain the dimension and BOM columns, foreign keys, and
  indexes required by the source schema.

## Work breakdown

### 1. Dimension matrix

1. Add a model-specific dimension matrix listing with server-side processing and
   responsive table behavior.
2. Add create/edit flows for size in inches and DN, pressure class, end connection,
   face-to-face, end-to-end, flange OD, center-to-top, weight, and extra dimensions.
3. Offer autocomplete suggestions for common size, DN, pressure-class, and
   end-connection values while accepting validated tenant-specific values.
4. Validate numeric dimensions as non-negative decimals with the specified precision.
5. Prevent duplicate `(valve_model_id, size_inch, pressure_class, end_connection)`
   combinations on both create and update.
6. Add a database unique constraint for that combination where supported, in
   addition to application validation, to close concurrent-write races.

### 2. BOM template manager

1. Add an ordered BOM editor linked to a valve model.
2. Persist part number, part name, standard material, and optional allowed material
   overrides according to the source schema.
3. Provide autocomplete for common part names and tenant-scoped searchable material
   selection.
4. Validate that all selected material IDs belong to the current tenant and are
   active.
5. Prevent duplicate `part_number` values within a model; add a database unique
   constraint to protect concurrent writes.
6. Keep BOM ordering deterministic by `part_number`.
7. Prevent deleting/deactivating referenced standard materials as specified by Phase
   3; validate optional material references as well.

### 3. Model readiness

1. Centralize readiness rules used by both the configurator model list and any model
   lookup endpoint.
2. A selectable model must be active and have at least one dimension row and one BOM
   row.
3. Explain missing readiness requirements to Company Admins in the model management
   UI.
4. Re-check readiness on the server when a datasheet is submitted; client filtering
   alone is insufficient.

## Acceptance criteria

- Dimension CRUD is isolated by tenant and parent model.
- Duplicate dimension tuples are rejected both by normal validation and by a database
  constraint under concurrent submissions.
- BOM rows are ordered and unique by model/part number.
- Cross-tenant or inactive material IDs cannot be inserted into BOM rows.
- Models without both at least one dimension and one BOM row are not selectable.
- Tests cover dimensions with nullable optional measurements, duplicate create/update,
  BOM ordering, duplicate part numbers, tenant material ownership, and readiness.

## Exit deliverables

- Dimension table/editor and duplicate-prevention constraints.
- BOM editor with searchable tenant materials and ordered callouts.
- Shared model-readiness rule and tests.
