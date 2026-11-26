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
   Keep this size-specific information on `ValveDimension`; the existing schema
   already contains these columns and does not need duplicate fields.
3. Offer autocomplete suggestions for common size, DN, pressure-class, and
   end-connection values while accepting validated tenant-specific values.
4. Validate numeric dimensions as non-negative decimals with the specified precision.
5. Prevent duplicate `(valve_model_id, size_inch, pressure_class, end_connection)`
   combinations on both create and update.
6. Add a database unique constraint for that combination where supported, in
   addition to application validation, to close concurrent-write races.

### 2. BOM template manager

1. Add an ordered BOM editor linked to a valve model.
2. Persist part number, part name, component group, quantity per valve, unit, notes,
   standard material, and optional allowed material overrides. Keep material and
   component-group values as strings in the database, with server-side validation
   for component groups.
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

## Implementation status

The Phase 4 dimension and BOM implementation is complete for the current MVP surface:

- Tenant migrations now create `valve_dimensions` and `valve_bom_templates` with
  parent foreign keys, decimal measurements, JSON extra dimensions, and database
  unique constraints for dimension tuples and model part numbers.
- An additive tenant migration adds reusable construction defaults to
  `valve_models` and component group, per-valve quantity, unit, and notes to BOM
  templates. Existing BOM rows retain their values and receive the safe defaults
  of quantity `1` and unit `ea`; the new descriptive fields remain nullable.
- Rolling back after those new fields contain tenant data will remove that data;
  prefer a forward-fix migration over rollback after deployment.
- All dimension measurements remain size-specific and are displayed in the model's
  dimension tab; no new dimension migration is needed because the required columns
  already exist.
- Valve-model pages link to tenant-admin dimension and BOM managers.
- Dimension and BOM configuration are consolidated into tabs on the valve-model
  edit page. New rows are created through native modal dialogs from the active tab.
- Dimension create/update validation rejects duplicate tuples and negative or
  over-precision measurements. Optional dimensions and extra measurements are
  persisted safely.
- BOM create/update validation rejects duplicate part numbers, inactive materials,
  invalid component groups, non-positive quantities, and material IDs outside the
  initialized tenant database.
- BOM rows are read deterministically by `part_number`, and referenced standard or
  optional materials cannot be deactivated.
- `ValveModelReadiness` is shared by model UI/DataTable and provides a selectable
  query requiring an active model with at least one dimension and one BOM row.

The focused Phase 4 suite passes in SQLite for nullable dimensions, duplicate
create/update tuples, database tuple constraints, BOM ordering and duplicate part
numbers, active tenant material validation, and shared model readiness.

### QA checklist

- Confirm a Company Admin can add, edit, and delete dimension rows for only the
  parent valve model.
- Confirm dimension and BOM tabs remain on the valve-model edit page and each
  add flow opens a modal without navigating away.
- Submit duplicate dimension combinations and confirm validation rejects them on
  create and update.
- Verify nullable measurements, extra dimensions, negative values, and values with
  excessive decimal precision are handled correctly.
- Confirm concurrent duplicate submissions are also rejected by the database unique
  constraint.
- Add BOM rows and confirm they display in ascending part-number order.
- Submit duplicate BOM part numbers and confirm create/update validation rejects
  them.
- Save a BOM row with a component group, fractional positive quantity, unit, and
  notes; confirm invalid groups and non-positive quantities are rejected.
- Confirm existing BOM rows show quantity `1 ea` after the additive migration.
- Confirm the dimension table displays face-to-face, end-to-end, flange OD,
  center-to-top, and weight values from the size-specific record.
- Confirm standard and optional material selections contain only active tenant
  materials and reject cross-tenant or inactive IDs.
- Try to deactivate a material referenced by a standard or optional BOM field and
  confirm it remains blocked until references are removed.
- Confirm inactive models and models missing dimensions or BOM rows are not shown
  as selectable and the model edit UI explains every missing requirement.
- Run the focused coverage with:

```sh
php artisan test --compact tests/Feature/TenantCatalogTest.php
```
