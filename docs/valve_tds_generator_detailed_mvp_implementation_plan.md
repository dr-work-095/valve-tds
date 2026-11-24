# Valve Technical Datasheet (TDS) Generator — Detailed MVP Implementation Plan

## 1. System Overview & Locked Tech Stack

| Layer / Domain | Technology & Architectural Standard |
| :--- | :--- |
| **Backend Framework** | **Laravel 13.\*** (PHP 8.3+ / 8.4+) |
| **Multi-Tenancy Engine** | **stancl/tenancy v3** (Database-per-Tenant Isolation: `tds_central` + `tds_{tenant_uid}`) |
| **Database Engine** | **MySQL 8.0+** (Strict mode, `utf8mb4`, InnoDB) |
| **Frontend & Asset Bundling** | **HTML Blade Templates + Vite + Tailwind CSS + Vanilla JS / jQuery** |
| **Form Input Controls** | **Native HTML Autocomplete (`<datalist>`) / Searchable Autocomplete Elements** for rapid part, material, size, and standard selection |
| **Listing & Grid Library** | **DataTables** (jQuery-based server-side AJAX processing, search, column filters, pagination) |
| **Document Export (MVP)** | **PDF Only** via **Spatie Browsershot** (Headless Chrome / Puppeteer) |
| **Queue & Cache Driver** | **Redis 7.x** (`high` and `low` priority queues + session driver) |
| **Billing Mode (MVP)** | **Manual Super Admin Management** (Offline GST Invoice / NEFT / UPI tracking) |

---

## 2. Database Schema Specifications

### 2.1 Central Database (`tds_central`)
Stores SaaS Super Admins, tenant profiles, manual subscription states, and central tenant user logins.

#### Table: `admins` (Super Admin Guard)
| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED`, PK, Auto-Increment | Primary identifier |
| `name` | `VARCHAR(255)`, NOT NULL | Super Admin full name |
| `email` | `VARCHAR(255)`, UNIQUE, NOT NULL | Login email for `/admin/login` |
| `password` | `VARCHAR(255)`, NOT NULL | Hashed password |
| `is_active` | `BOOLEAN`, DEFAULT `true` | Allows disabling an operator account without deletion |
| `remember_token` | `VARCHAR(100)`, NULLABLE | Session persistence |
| `created_at`, `updated_at` | `TIMESTAMP` | Audit timestamps |

#### Table: `tenants` (Registered Valve Companies)
| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED`, PK, Auto-Increment | Internal numeric tenant identifier |
| `tenant_uid` | `VARCHAR(64)`, UNIQUE, NOT NULL | Tenant business/tenancy key (e.g., `tenant_northplant`) |
| `company_name` | `VARCHAR(255)`, NOT NULL | Registered valve company name |
| `plan` | `ENUM('starter', 'growth', 'pro')`, DEFAULT `'starter'` | Active subscription tier |
| `status` | `ENUM('trial', 'active', 'expired', 'suspended')`, DEFAULT `'trial'` | Current account access state |
| `subscription_ends_at` | `TIMESTAMP`, NULLABLE | Cutoff date/time for active subscription |
| `admin_notes` | `TEXT`, NULLABLE | Offline payment reference (UTR/NEFT/GST Invoice #) |
| `data` | `JSON`, NULLABLE | Internal `stancl/tenancy` metadata (e.g., `tenancy_db_name`) |
| `created_at`, `updated_at` | `TIMESTAMP` | Audit timestamps |

#### Table: `users` (Tenant Users — Web Guard)
| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED`, PK, Auto-Increment | Primary identifier |
| `tenant_id` | `VARCHAR(64)`, FK -> `tenants.tenant_uid` (CASCADE), NOT NULL | Membership value that binds a user to one tenant UID/database |
| `name` | `VARCHAR(255)`, NOT NULL | Employee full name |
| `email` | `VARCHAR(255)`, UNIQUE, NOT NULL | Central login email |
| `password` | `VARCHAR(255)`, NOT NULL | Hashed password |
| `role` | `ENUM('admin', 'sales')`, DEFAULT `'sales'` | Tenant-level permission role |
| `is_active` | `BOOLEAN`, DEFAULT `true` | Allows Company Admin to deactivate staff |
| `created_at`, `updated_at` | `TIMESTAMP` | Audit timestamps |

---

### 2.2 Tenant Database (`tds_{tenant_uid}`)
Automatically provisioned per company. Each tenant maintains its own isolated master catalogs (`materials`, `valve_models`, `valve_dimensions`, `valve_bom_templates`).

#### Table: `company_settings` (Single-Row Configuration)
| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `INT UNSIGNED`, PK, Auto-Increment | Single row (`id = 1`) |
| `company_name` | `VARCHAR(255)`, NOT NULL | Display name on PDF header |
| `address` | `TEXT`, NULLABLE | Factory / office address |
| `tax_id_gst` | `VARCHAR(50)`, NULLABLE | GSTIN / Tax registration number |
| `logo_path` | `VARCHAR(500)`, NULLABLE | Private path inside tenant storage |
| `header_text` | `VARCHAR(255)`, NULLABLE | Custom top banner subtitle |
| `footer_text` | `VARCHAR(255)`, NULLABLE | Custom bottom legal/ISO text |
| `default_notes` | `JSON`, NULLABLE | Default testing/inspection notes pre-filled on new TDS |
| `updated_at` | `TIMESTAMP` | Last updated timestamp |

#### Table: `materials` (Tenant-Scoped Standard Material Catalog)
Pre-seeded automatically during `ProvisionTenantDatabase` with ~25–30 standard valve industry grades (e.g., *ASTM A216 WCB, ASTM A351 CF8M, SS316, PTFE, Monel 400*), while allowing each Company Admin to add, edit, or deactivate custom alloys.

| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED`, PK, Auto-Increment | Primary identifier |
| `code` | `VARCHAR(100)`, UNIQUE, NOT NULL | Standard grade code (e.g., `ASTM A216 WCB`, `PTFE`) |
| `name` | `VARCHAR(150)`, NULLABLE | Friendly description (e.g., *Carbon Steel Cast*, *Stainless Steel 316*) |
| `category` | `VARCHAR(50)`, NOT NULL | Grouping: `Body/Bonnet`, `Trim/Stem`, `Seat/Seal`, `Fasteners` |
| `is_active` | `BOOLEAN`, DEFAULT `true` | Controls visibility in autocomplete suggestions |
| `created_at`, `updated_at` | `TIMESTAMP` | Audit timestamps |

#### Table: `valve_models` (Master Product Series)
| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED`, PK, Auto-Increment | Primary identifier |
| `name` | `VARCHAR(255)`, NOT NULL | e.g., *Bolted Bonnet Gate Valve* |
| `category` | `VARCHAR(100)`, NOT NULL | e.g., *Gate, Globe, Ball, Check, Butterfly* |
| `design_standard` | `VARCHAR(255)`, NOT NULL | e.g., *API 600 / BS 1414* |
| `testing_standard` | `VARCHAR(255)`, NOT NULL | e.g., *API 598 / EN 12266-1* |
| `face_to_face_standard` | `VARCHAR(255)`, NULLABLE | e.g., *ASME B16.10* |
| `end_connection_standard` | `VARCHAR(255)`, NULLABLE | e.g., *ASME B16.5* |
| `schematic_image_path` | `VARCHAR(500)`, NULLABLE | 2D GA line drawing path in private storage |
| `custom_attributes` | `JSON`, NULLABLE | Optional key-value specs (e.g., *Bore Type: Full Bore*) |
| `is_active` | `BOOLEAN`, DEFAULT `true` | Hides discontinued models from sales wizard |
| `created_at`, `updated_at` | `TIMESTAMP` | Audit timestamps |

#### Table: `valve_dimensions` (Size & Pressure Matrix)
| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED`, PK, Auto-Increment | Primary identifier |
| `valve_model_id` | `BIGINT UNSIGNED`, FK -> `valve_models.id` (CASCADE) | Parent valve model |
| `size_inch` | `VARCHAR(20)`, NOT NULL | e.g., `1/2"`, `2"`, `4"`, `12"` |
| `size_dn` | `VARCHAR(20)`, NULLABLE | e.g., `DN15`, `DN50`, `DN100` |
| `pressure_class` | `VARCHAR(50)`, NOT NULL | e.g., `Class 150`, `Class 300`, `PN16` |
| `end_connection` | `VARCHAR(50)`, NOT NULL | e.g., `Flanged RF`, `Butt Weld`, `RTJ` |
| `face_to_face_mm` | `DECIMAL(10,2)`, NULLABLE | Face-to-Face length in mm |
| `end_to_end_mm` | `DECIMAL(10,2)`, NULLABLE | End-to-End length in mm |
| `flange_od_mm` | `DECIMAL(10,2)`, NULLABLE | Flange outer diameter in mm |
| `center_to_top_mm` | `DECIMAL(10,2)`, NULLABLE | Valve height in mm |
| `weight_kg` | `DECIMAL(10,2)`, NULLABLE | Approximate net weight in kg |
| `extra_dimensions` | `JSON`, NULLABLE | Custom dimensional columns |
| *Composite Index* | `(valve_model_id, size_inch, pressure_class, end_connection)` | Fast lookup & duplicate prevention |

#### Table: `valve_bom_templates` (Bill of Materials Rules)
| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED`, PK, Auto-Increment | Primary identifier |
| `valve_model_id` | `BIGINT UNSIGNED`, FK -> `valve_models.id` (CASCADE) | Parent valve model |
| `part_number` | `INT UNSIGNED`, NOT NULL | Callout number matching 2D GA drawing (`1, 2, 3...`) |
| `part_name` | `VARCHAR(150)`, NOT NULL | e.g., *Body, Bonnet, Wedge, Stem, Seat Ring* |
| `standard_material_id` | `BIGINT UNSIGNED`, FK -> `materials.id` (RESTRICT) | Default material from tenant `materials` catalog |
| `optional_material_ids` | `JSON`, NULLABLE | Array of allowed `materials.id` overrides for sales dropdown/autocomplete |
| *Composite Index* | `(valve_model_id, part_number)` | Enforces ordered BOM display |

#### Table: `generated_datasheets` (Immutable Quote & TDS Registry)
| Column | Type & Constraints | Purpose |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED`, PK, Auto-Increment | Primary identifier |
| `created_by_user_id` | `BIGINT UNSIGNED`, NOT NULL | References central `users.id` for audit trail |
| `valve_model_id` | `BIGINT UNSIGNED`, FK -> `valve_models.id` (RESTRICT) | Reference to source model |
| `document_number` | `VARCHAR(100)`, NOT NULL | Auto-generated or project tag (e.g., `TDS-2026-0014`) |
| `revision` | `VARCHAR(10)`, DEFAULT `'Rev 0'` | Revision counter (`Rev 0`, `Rev 1`, `Rev 2`) |
| `parent_datasheet_id` | `BIGINT UNSIGNED`, NULLABLE | Links revision back to original datasheet |
| `client_name` | `VARCHAR(255)`, NOT NULL | Customer / EPC buyer name |
| `project_reference` | `VARCHAR(255)`, NULLABLE | RFQ / Tender reference number |
| `selected_specs` | `JSON`, NOT NULL | **Immutable full snapshot** (company info, model, dimensions, resolved BOM material strings, notes) |
| `status` | `ENUM('queued', 'generated', 'failed')`, DEFAULT `'queued'` | PDF rendering job state |
| `pdf_path` | `VARCHAR(500)`, NULLABLE | Private storage path of rendered PDF |
| `created_at`, `updated_at` | `TIMESTAMP` | Audit timestamps |
| *Composite Unique* | `(document_number, revision)` | Prevents duplicate revision numbers per document |

---

## 3. Detailed Sub-Phases Implementation Plan (MVP)

### Sub-Phase 1.1: Core Setup, Dual Guards & Session-Bound Tenancy
* **Deliverables:**
  * Fresh **Laravel 13.\*** installation configured with **Vite + Tailwind CSS + jQuery/DataTables** and `stancl/tenancy`.
  * Two separate authentication guards configured in `config/auth.php`:
    1. `admin` guard -> backed by central `admins` table (`/admin/*` routes).
    2. `web` guard -> backed by central `users` table (tenant app routes).
  * Custom `InitializeUserTenant` middleware registered after `Authenticate` on the `web` route group.
* **Logic Conditions:**
  * **Guard Isolation:** Requests to `/admin/*` must never initialize a tenant database connection; they operate exclusively on `tds_central`.
  * **Tenant Resolution:** When a `web` user authenticates, the middleware reads `auth()->user()->tenant_id`, resolves `tenants.tenant_uid`, and initializes that tenant. It never accepts a tenant UID from a request parameter. If `tenant_id` is missing or `users.is_active == false`, force logout and redirect to login.
  * **Subscription Gatekeeper:** Before calling `tenancy()->initialize($tenant)`, check the central `tenants` record:
    * If `status` is `'suspended'` or `'expired'`, block access and show a *"Subscription Inactive — Contact Administrator"* screen.
    * If `subscription_ends_at` is in the past (`now() > subscription_ends_at`), automatically update `status` to `'expired'` and block access.

---

### Sub-Phase 1.2: Super Admin Portal (Minimal Required Scope)
* **Scope Restriction:** Keep strictly to essential tenant and manual subscription management. No revenue charts, no automated payment gateways, and no complex SaaS analytics.
* **Deliverables:**
  * Super Admin Login (`/admin/login`) & Minimal Dashboard (Total Tenants, Active Tenants, Expiring in 7 Days count).
  * **Tenants Management Screen** powered by **DataTables** (`Company Name`, `Plan`, `Status`, `Subscription Ends At`, `Actions`).
  * **Create Tenant Modal/Form:** Inputs for `company_name`, `plan`, `status`, `subscription_ends_at`, `admin_notes`, plus initial Company Admin credentials (`admin_name`, `admin_email`, `temporary_password`).
  * **Edit / Renew Tenant Screen:** Update `plan`, extend `subscription_ends_at`, toggle `status` (`active` / `suspended`), and append offline payment notes.
  * **Automated Provisioning Job (`ProvisionTenantDatabase`):** Triggered automatically when a tenant is created.
* **Logic Conditions:**
  * **Database Provisioning Sequence:**
    1. Create `tenants` record in `tds_central`.
    2. Create initial `users` record (`role = 'admin'`) in `tds_central`.
    3. Generate a unique `tenant_uid` from the company name using lowercase alphanumeric characters, for example `tenant_northplant`; duplicate names receive a numeric suffix. Dispatch `ProvisionTenantDatabase` to create MySQL database `tds_{tenant_uid}`, run tenant migrations, seed `company_settings` (`id = 1`), **pre-seed the tenant's `materials` catalog** with standard ASTM/ASME/PTFE grades, and create private directory `storage/app/tenants/{tenant_uid}/{logos,schematics,datasheets}`.
  * **Plan Limits Enforcement (Checked during Tenant Usage):**
    * `starter`: Max **2** active users, Max **5** active valve models, Max **100** PDFs/month.
    * `growth`: Max **8** active users, **Unlimited** valve models, **Unlimited** PDFs.
    * `pro`: Max **20** active users, **Unlimited** valve models, **Unlimited** PDFs.

---

### Sub-Phase 1.3: Tenant Settings, Materials Catalog, Media Proxy & Valve Models CRUD
* **Deliverables:**
  * **Company Settings Page** (`role = 'admin'`): Edit company details, GSTIN, header/footer text, default notes, and upload company logo with Vanilla JS/jQuery image preview.
  * **Materials Catalog Page** (`role = 'admin'`): Powered by **DataTables** (`Code`, `Description`, `Category`, `Status`, `Actions`) so each tenant can manage their own material grades.
  * **Authenticated Media Streaming Route:** Serves logos and 2D GA drawings securely from private tenant storage (`storage/app/tenants/{tenant_uid}/...`) without public symlinks.
  * **Valve Models Listing Page** powered by **DataTables** (Columns: `Model Name`, `Category`, `Design Standard`, `Testing Standard`, `Status`, `Actions` [Dimensions, BOM, Edit, Delete]).
  * **Valve Model Create/Edit Form:** Includes 2D GA schematic upload (PNG/JPG/SVG) with live preview, **HTML autocomplete inputs** (`<datalist>`) for standard engineering standards (`API 600`, `API 6D`, `API 598`, `ASME B16.10`), and Vanilla JS/jQuery dynamic key-value `custom_attributes` row builder.
* **Logic Conditions:**
  * **Role Restriction:** Users with `role = 'sales'` attempting to access `/settings`, `/materials`, `/valve-models`, or `/team` receive a `403 Forbidden` response.
  * **Media Isolation Check:** When serving an image via the media controller, verify `auth()->check()` and resolve the file strictly from the currently initialized `tenant('id')` folder so one tenant cannot guess another tenant's file path.
  * **Material Deletion Protection:** Prevent deleting a material from `materials` if it is referenced by any active `valve_bom_templates` row (allow setting `is_active = false` instead).
  * **Plan Quota Check:** Block creating a new Valve Model if `plan == 'starter'` and `ValveModel::where('is_active', true)->count() >= 5`.

---

### Sub-Phase 1.4: Dimension Matrix & BOM Template Management (With HTML Autocomplete)
* **Deliverables:**
  * **Dimension Matrix Manager** per Valve Model (DataTables listing + modal/inline Add & Edit using **HTML autocomplete elements** for standard `Size Inch`, `Size DN`, `Pressure Class`, and `End Connection` values alongside `Face-to-Face mm` and `Weight kg`).
  * **BOM Template Manager** embedded in or linked from Valve Model (Ordered table by `Part Number` using **HTML autocomplete elements** for `Part Name` suggestions and searchable material selection from the tenant's `materials` catalog for `Standard Material` and `Optional Materials`).
* **Logic Conditions:**
  * **Duplicate Matrix Prevention:** Reject saving a dimension row if the combination of `(valve_model_id, size_inch, pressure_class, end_connection)` already exists.
  * **Duplicate BOM Part Prevention:** Reject saving a BOM row if `part_number` is already assigned within the same `valve_model_id`.
  * **Model Readiness Validation:** A Valve Model cannot be selected in the Sales Configurator unless it has **at least 1 Dimension row** and **at least 1 BOM row** configured.

---

### Sub-Phase 1.5: Sales Configurator Form, Snapshots & Revision Control
* **Deliverables:**
  * **Datasheets Listing Page** powered by **DataTables** (Columns: `Doc No.`, `Revision`, `Client Name`, `Valve Model`, `Size & Class`, `Created By`, `Status`, `Created Date`, `Actions` [Download PDF, Create Revision]).
  * **Interactive Configurator Form (Blade + HTML Autocomplete + Vanilla JS / jQuery AJAX):**
    1. Enter `client_name` (with **HTML autocomplete** from previous client names), `project_reference`, and `document_number` (auto-suggested, editable).
    2. Select **Valve Model** (searchable/autocomplete selector) -> triggers a jQuery/Fetch AJAX GET request to dynamically populate available **Sizes**, **Pressure Classes**, and **End Connections** from `valve_dimensions`.
    3. Selecting size/class/end-connection auto-populates matched mechanical dimensions (`Face-to-Face`, `Weight`, etc.) and renders the model's BOM rows into the DOM.
    4. Salesperson picks material overrides per BOM part from pre-approved options (or searchable material autocomplete) and edits project notes.
  * **Create Revision Action:** Clones any existing datasheet into `Rev N+1`.
* **Logic Conditions:**
  * **Monthly PDF Quota Check:** If `plan == 'starter'`, count `generated_datasheets` created in the current calendar month; if `>= 100`, block creation and prompt upgrade.
  * **Immutable JSON Snapshot Freeze:** On form submission, resolve all material IDs into their exact text strings (`code` + `name`) and compile all active `company_settings`, `valve_models` metadata, matched `valve_dimensions` row, selected BOM materials, and notes into `selected_specs` JSON. Subsequent edits to master catalog tables (`materials`, `valve_models`, `valve_dimensions`) must never alter existing `generated_datasheets`.
  * **Revision Increment Logic:** When cloning a datasheet (`parent_datasheet_id`), query the highest `revision` number for that `document_number` and increment by `1` (`Rev 0` -> `Rev 1` -> `Rev 2`). Keep all prior revisions locked and downloadable.

---

### Sub-Phase 1.6: PDF Generation Engine & Priority Queue Setup
* **Deliverables:**
  * **Print-Ready Blade PDF Template:** A4 portrait engineering layout (`8mm` margins), company letterhead + logo, document/revision metadata block, side-by-side 2D GA drawing + BOM table, design standards block, dimensions table, notes, and sign-off footer.
  * **Background Job (`GenerateDatasheetPdfJob`):** Runs on the `high` priority Redis queue using **Spatie Browsershot** (Headless Chrome) and saves output to `storage/app/tenants/{tenant_uid}/datasheets/`.
  * **Fan-Out Nightly Scheduler:** Runs once daily via `chunkById(100)` to expire overdue trial/active tenants in `tds_central`.
* **Logic Conditions:**
  * **Queue Priority Routing:** User-triggered `GenerateDatasheetPdfJob` dispatches to `--queue=high` (timeout: `60s`, tries: `3`). Scheduled maintenance jobs dispatch to `--queue=low`.
  * **Download Readiness Guard:** In the DataTables listing, show a *"Generating..."* badge while `status == 'queued'`, enable the **Download PDF** button once `status == 'generated'`, and show a **Retry PDF** button if `status == 'failed'`.

---

## 4. UI Standards: DataTables & HTML Autocomplete Elements

### 4.1 DataTables Standards (All Listings)
Every tabular listing in both the Super Admin Portal and Tenant App uses **DataTables** (with jQuery bundled via Vite):
* **Server-Side Processing:** Enabled (`serverSide: true`, `processing: true`) for `generated_datasheets`, `valve_dimensions`, `materials`, and `tenants` so queries remain fast as records grow.
* **Standard Controls:** Global search box, per-column dropdown filters (e.g., filter datasheets by *Valve Model*, *Status*, or *Sales Engineer*; filter materials by *Category*), page length selector (`10 / 25 / 50 / 100`), and responsive horizontal scroll.
* **Tenant Isolation:** Every DataTables AJAX endpoint runs inside the `InitializeUserTenant` middleware, guaranteeing queries execute strictly against the authenticated user's `tds_{tenant_uid}` database.

### 4.2 HTML Autocomplete Standards (All Forms)
To minimize typing and eliminate spelling inconsistencies across engineering forms, all repetitive text and catalog inputs use **native HTML `<input list="...">` + `<datalist>` autocomplete elements** (or lightweight jQuery-enhanced autocomplete inputs where ID binding is required):
* **Valve Model Form:** Autocomplete suggestions for `category`, `design_standard`, `testing_standard`, `face_to_face_standard`, and `end_connection_standard`.
* **Dimension Matrix Form:** Autocomplete suggestions for `size_inch`, `size_dn`, `pressure_class`, and `end_connection`.
* **BOM Template Form:** Autocomplete suggestions for standard valve `part_name` values (*Body, Bonnet, Ball, Wedge, Disc, Stem, Seat Ring, Gland Packing, Body Gasket, Studs & Nuts*) and searchable autocomplete against the tenant's `materials` catalog.
* **Sales Configurator Form:** Autocomplete suggestions for returning `client_name` values and searchable valve model/material selection.

---

## 5. Production VPS Deployment Checklist

### Step 1: Server Provisioning & Base Stack
1. Provision an **Ubuntu 22.04 / 24.04 LTS VPS** (Minimum **4 vCPU / 8 GB RAM** to run Headless Chrome comfortably alongside PHP-FPM and MySQL).
2. Install core packages:
   * **Nginx** (web server with SSL via Certbot)
   * **PHP 8.3 / 8.4 FPM** with extensions: `mbstring`, `xml`, `bcmath`, `curl`, `zip`, `gd`, `mysql`, `redis`, `intl`
   * **MySQL 8.0+** & **Redis 7.x**
   * **Node.js (LTS) & npm** (for Vite production asset compilation and Puppeteer)

### Step 2: Headless Chrome Setup (For Spatie Browsershot PDF)
1. Install Chromium / Google Chrome and required Linux font/graphics libraries (`libnss3`, `libatk-bridge2.0-0`, `libgbm1`, `libasound2`, `fonts-liberation`).
2. Install `puppeteer` globally or inside the project root and verify Browsershot can render a test PDF from CLI.

### Step 3: MySQL Multi-Tenant User Privileges
1. Create the central database `tds_central`.
2. Create a dedicated MySQL application user (`valvetds_user`) and grant:
   * `ALL PRIVILEGES` on `tds_central.*`
   * `CREATE`, `DROP`, `ALTER`, and `ALL PRIVILEGES` on wildcard `tds_tenant_%.*` so `ProvisionTenantDatabase` can dynamically create and migrate tenant schemas named with `tenant_uid`.

### Step 4: Application Deployment & Asset Compilation
1. Clone repository to `/var/www/valvetds` and configure `.env` (`APP_ENV=production`, `APP_DEBUG=false`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`, central DB credentials).
2. Install PHP dependencies (`composer install --no-dev --optimize-autoloader`).
3. Compile frontend **Vite + Tailwind + jQuery/DataTables** assets (`npm ci && npm run build`).
4. Run central migrations (`php artisan migrate --force`) and seed the initial Super Admin record into `admins`.
5. Set directory ownership (`www-data:www-data`) and `775` permissions on `storage/` and `bootstrap/cache/`.

### Step 5: Queue Workers (Supervisor) & Cron Scheduler
1. Configure **Supervisor** (`/etc/supervisor/conf.d/valvetds-worker.conf`) to manage **2–4 worker processes** listening to priority queues in order: `--queue=high,low --sleep=3 --tries=3 --timeout=60`.
2. Add the single **Laravel Scheduler Cron** entry under `www-data` crontab (`* * * * * cd /var/www/valvetds && php artisan schedule:run >> /dev/null 2>&1`).
3. Configure daily automated off-site backups (`mysqldump` for `tds_central` + all `tds_tenant_*` databases + `storage/app/tenants/`).