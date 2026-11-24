# Valve TDS Generator MVP — Phase Plans

These phase plans expand the source specification in
[`../valve_tds_generator_detailed_mvp_implementation_plan.md`](../valve_tds_generator_detailed_mvp_implementation_plan.md)
into sequenced implementation work and verifiable exit criteria.

## Plan sequence

| Order | Plan | Outcome |
|---|---|---|
| 1 | [Core setup, dual guards, and session-bound tenancy](01-core-setup-and-tenant-auth.md) | Central identity and safe tenant initialization are in place. |
| 2 | [Super Admin portal and tenant provisioning](02-super-admin-and-provisioning.md) | Operators can manage subscriptions and provision isolated tenant databases. |
| 3 | [Tenant settings, materials, media, and valve models](03-tenant-catalogs-and-models.md) | Tenant administrators can configure company data and product models. |
| 4 | [Dimension matrix and BOM templates](04-dimensions-and-bom.md) | Models have validated size/pressure data and ordered material rules. |
| 5 | [Sales configurator, snapshots, and revisions](05-sales-configurator-and-revisions.md) | Sales users can create immutable, revision-controlled datasheet records. |
| 6 | [PDF generation and priority queues](06-pdf-generation-and-queues.md) | Datasheets render asynchronously and can be securely downloaded or retried. |
| 7 | [Production VPS deployment and operations](07-production-deployment.md) | The application is deployed with workers, scheduler, and backups. |

## Cross-phase requirements

- Use the locked stack and database-per-tenant architecture from the source plan. Do
  not substitute a shared tenant-data schema or add an automated billing gateway to
  the MVP.
- Treat the central database as the source for admins, tenant accounts, and web-user
  identities; tenant operational records belong only in the resolved tenant database.
- Keep `tenants.id` as the numeric primary key and use `tenants.tenant_uid` for
  tenancy resolution, tenant database names, private storage paths, and membership
  references. Do not expose or accept the UID as caller-controlled tenant context.
- Tenant-scoped web and AJAX requests must pass through authenticated tenant
  initialization. Admin routes must remain on the central connection.
- Keep tenant uploads and generated PDFs private. Resolve media paths using the
  authenticated tenant context, not a caller-supplied tenant or arbitrary path.
- Use server-side DataTables for the listings specified in the source plan, with
  search, filters, pagination, and responsive horizontal scrolling.
- Use HTML `<datalist>` or the specified lightweight searchable autocomplete pattern
  for repeated engineering values; validate all submitted values on the server.
- Preserve historical datasheets as immutable snapshots. Changes to live catalog or
  company settings must not rewrite earlier records or PDFs.
- Keep implementation and test details aligned with the actual application version,
  installed packages, database setup, and project conventions. The source plan's
  target stack is a specification; verify compatibility before introducing packages.

## Dependency overview

`01` is the foundation. `02` depends on central authentication and tenant identity
from `01`. `03` depends on successful tenant provisioning from `02`. `04` depends on
the models and materials catalog from `03`. `05` depends on readiness-validated
models and BOM/dimension data from `04`. `06` depends on immutable datasheet records
from `05`. `07` is the release/operations phase and should be completed after the
application and PDF workflow have passed their pre-production checks.

The source plan leaves several operational details to implementation (such as exact
tenant database naming, storage cleanup after a failed provisioning job, and the
atomicity strategy for revision numbering). Record and test these decisions during
the relevant phase rather than silently weakening the specified isolation or
immutability requirements.
