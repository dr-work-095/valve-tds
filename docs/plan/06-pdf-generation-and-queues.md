# Phase 6 — PDF Generation and Priority Queues

## Goal

Render immutable datasheet snapshots into private, print-ready PDFs using a
background high-priority queue, and provide reliable status, download, and retry
behavior.

## Prerequisites

- Complete [Phase 5](05-sales-configurator-and-revisions.md).
- Verify Redis, queue workers, Browsershot, Puppeteer/Chrome, and required runtime
  libraries in the target environment.
- Confirm the PDF worker can securely re-establish the correct tenant connection and
  storage context from a queued job without relying on the originating HTTP request.

## Work breakdown

### 1. PDF template

1. Create a print-only Blade view driven exclusively by the persisted snapshot.
2. Implement A4 portrait layout with 8 mm margins.
3. Include company letterhead/logo, document number/revision, client/project data,
   side-by-side schematic and ordered BOM, standards, dimensions, notes, and
   sign-off/footer.
4. Handle absent optional logo, drawing, dimensions, and notes without broken layout
   or external network access.
5. Add visual/fixture checks for page size, overflow, Unicode/engineering text, image
   sizing, and multi-page behavior.

### 2. PDF job and private storage

1. Implement `GenerateDatasheetPdfJob` and dispatch it to Redis queue `high`.
2. Store only identifiers needed to load the record and tenant context; load the
   immutable snapshot at execution time.
3. Re-establish tenant context explicitly in the worker and always clean it up.
4. Render with Browsershot using the supported Chrome/Puppeteer configuration.
5. Save output under the tenant's private datasheets directory with a safe,
   collision-resistant filename.
6. Mark the record `generated` and persist its private path only after the file has
   been written successfully.
7. On failure, mark the record `failed`, log useful context without leaking secrets,
   and preserve enough information for an operator to diagnose/retry.
8. Configure retries, timeout, and failure handling consistent with the source
   requirements (3 tries, 60 seconds); ensure job retries do not expose partial PDFs.

### 3. Status, download, and retry

1. Show `queued` as “Generating…” and prevent downloading until the generated state
   and file both exist.
2. Add an authenticated download endpoint that verifies tenant ownership, generated
   status, and the resolved private storage path.
3. Add a retry action for failed jobs with authorization, tenant checks, and safe
   status transition.
4. Keep PDF versions associated with their immutable datasheet revision.

### 4. Queue priority and expiry scheduler

1. Configure workers to consume `high` before `low`; user-triggered PDF jobs use
   `high`.
2. Add a daily maintenance job on `low` that scans overdue trial/active tenants in
   `chunkById(100)` batches and marks them expired centrally.
3. Make the expiry operation idempotent and ensure it does not overwrite suspended
   status or other valid administrative state.
4. Register the scheduler command and test the date/status boundaries.

## Acceptance criteria

- A generated PDF matches the persisted snapshot even if live settings/catalogs
  change after datasheet creation.
- Jobs run in a worker without an HTTP request and initialize/reset tenant context
  safely.
- Success updates status/path only after a valid private PDF exists; failures are
  visible and retryable.
- Cross-tenant download attempts and path manipulation are rejected.
- `high` jobs are prioritized over `low`; expiry processing runs daily in bounded
  chunks and is idempotent.
- Tests cover queued/generated/failed transitions, retries, secure downloads,
  snapshot rendering, and expiry scheduling. Include an environment-level smoke test
  that renders a real PDF with Chrome.

## Exit deliverables

- Print-ready PDF Blade view and rendering job.
- Redis queue routing, worker configuration, failure/retry handling.
- Secure PDF download endpoint and status-aware listing actions.
- Nightly expiry scheduler and focused tests.

## Implementation status

Phase 6 is implemented for the current MVP surface:

- Datasheet creation dispatches `GenerateDatasheetPdfJob` to the `high` queue.
- The job re-establishes tenant context, renders only the persisted snapshot, writes
  to the tenant-private datasheets directory, and marks records generated or failed.
- `spatie/browsershot` and Puppeteer are installed; Chrome is configurable through
  `CHROME_PATH`.
- Job retries use 3 attempts with a 60-second timeout and are queued with backoff.
- Generated datasheets expose secure tenant-owned downloads; failed datasheets expose
  retry actions; queued datasheets show Generating.
- The listing uses the same search/DataTable/status/action conventions as existing
  tenant catalog pages.
- `app:expire-overdue-tenants` is registered and scheduled daily at 01:00 in bounded
  `chunkById(100)` batches.

### QA checklist

- Create a datasheet and confirm a PDF job is dispatched to `high`.
- Start a worker with `php artisan queue:work --queue=high,low --tries=3 --timeout=60`
  and confirm the row changes from Queued/Generating to Generated.
- Download a generated PDF and confirm it opens privately for only the owning tenant.
- Attempt to download another tenant's datasheet or manipulate the PDF path and
  confirm the request fails.
- Force rendering failure, confirm the row becomes Failed, then retry and confirm
  status returns to Queued followed by Generated.
- Edit live company/model/material records after creation and confirm the PDF uses
  the unchanged snapshot.
- Verify A4/8 mm margins, Unicode text, optional logo/drawing, BOM ordering, overflow,
  and multi-page behavior.
- Run `php artisan app:expire-overdue-tenants` and confirm overdue trial/active tenants
  expire while suspended tenants remain unchanged.
- Run:

```sh
php artisan test --compact tests/Feature/TenantPdfTest.php tests/Feature/TenantConfiguratorTest.php
php artisan schedule:list
```
